<?php
/**
* @package JAMA
*/

define('RAND_MAX', mt_getrandmax());
define('RAND_MIN', 0);

require_once 'PHPExcel/Shared/JAMA/utils/Error.php';
require_once 'PHPExcel/Shared/JAMA/utils/Maths.php';
require_once 'PHPExcel/Shared/JAMA/CholeskyDecomposition.php';
require_once 'PHPExcel/Shared/JAMA/LUDecomposition.php';
require_once 'PHPExcel/Shared/JAMA/QRDecomposition.php';
require_once 'PHPExcel/Shared/JAMA/EigenvalueDecomposition.php';
require_once 'PHPExcel/Shared/JAMA/SingularValueDecomposition.php';

/*
* Matrix class
* @author Paul Meagher
* @author Michael Bommarito
* @author Lukasz Karapuda
* @author Bartek Matosiuk
* @version 1.8
* @license PHP v3.0
* @see http://math.nist.gov/javanumerics/jama/
*/
class Matrix {
  /**
  * Matrix storage
  * @var array
  * @access private
  */
  var $A = array();

  /**
  * Matrix row dimension
  * @var int
  * @access private
  */
  var $m;

  /**
  * Matrix column dimension
  * @var int
  * @access private
  */
  var $n;

  /**
  * Polymorphic constructor
  * As PHP has no support for polymorphic constructors, we hack our own sort of polymorphism using func_num_args, func_get_arg, and gettype.  In essence, we're just implementing a simple RTTI filter and calling the appropriate constructor.
  * @return
  */
  function Matrix() {
    if( func_num_args() > 0 ) {

      $args = func_get_args();
      $match = implode(",", array_map('gettype', $args));

      switch( $match ) {

        //Square matrix - n x n
        case 'integer':
          $this->m = $args[0];
          $this->n = $args[0];
          $this->A = array_fill(0, $this->m, array_fill(0, $this->n, 0));
          break;

        //Rectangular matrix - m x n
        case 'integer,integer':
          $this->m = $args[0];
          $this->n = $args[1];
          $this->A = array_fill(0, $this->m, array_fill(0, $this->n, 0));
          break;

        //Rectangular matrix constant-filled - m x n filled with c
        case 'integer,integer,integer':
          $this->m = $args[0];
          $this->n = $args[1];
          $this->A = array_fill(0, $this->m, array_fill(0, $this->n, $args[2]));
          break;

        //Rectangular matrix constant-filled - m x n filled with c
        case 'integer,integer,double':
          $this->m = $args[0];
          $this->n = $args[1];
          $this->A = array_fill(0, $this->m, array_fill(0, $this->n, $args[2]));
          break;

        //Rectangular matrix - m x n initialized from 2D array
        case 'array':
          $this->m = count($args[0]);
          $this->n = count($args[0][0]);
          $this->A = $args[0];
          break;

        //Rectangular matrix - m x n initialized from 2D array
        case 'array,integer,integer':
          $this->m = $args[1];
          $this->n = $args[2];
          $this->A = $args[0];
          break;

        //Rectangular matrix - m x n initialized from packed array
        case 'array,integer':
          $this->m = $args[1];

          if ($this->m != 0)
            $this->n = count($args[0]) / $this->m;
          else
            $this->n = 0;

          if ($this->m * $this->n == count($args[0]))
            for($i = 0; $i < $this->m; $i++)
              for($j = 0; $j < $this->n; $j++)
                $this->A[$i][$j] = $args[0][$i + $j * $this->m];
          else
            trigger_error(ArrayLengthException, ERROR);

          break;
        default:
          trigger_error(PolymorphicArgumentException, ERROR);
          break;
      }
    } else
      trigger_error(PolymorphicArgumentException, ERROR);
  }

  /**
  * getArray
  * @return array Matrix array
  */
  function &getArray() {
    return $this->A;
  }

  /**
  * getArrayCopy
  * @return array Matrix array copy
  */
  function getArrayCopy() {
    return $this->A;
  }

  /** Construct a matrix from a copy of a 2-D array.
  * @param double A[][]  Two-dimensional array of doubles.
  * @exception  IllegalArgumentException All rows must have the same length
  */
  function constructWithCopy($A) {
    $this->m = count($A);
    $this->n = count($A[0]);
    $X = new Matrix($this->m, $this->n);
    for ($i = 0; $i < $this->m; $i++) {
      if (count($A[$i]) != $this->n)
        trigger_error(RowLengthException, ERROR);
      for ($j = 0; $j < $this->n; $j++)
        $X->A[$i][$j] = $A[$i][$j];
    }
    return $X;
  }

  /**
  * getColumnPacked
  * Get a column-packed array
  * @return array Column-packed matrix array
  */
  function getColumnPackedCopy() {
    $P = array();
    for($i = 0; $i < $this->m; $i++) {
      for($j = 0; $j < $this->n; $j++) {
        array_push($P, $this->A[$j][$i]);
      }
    }
    return $P;
  }

  /**
  * getRowPacked
  * Get a row-packed array
  * @return array Row-packed matrix array
  */
  function getRowPackedCopy() {
    $P = array();
    for($i = 0; $i < $this->m; $i++) {
      for($j = 0; $j < $this->n; $j++) {
        array_push($P, $this->A[$i][$j]);
      }
    }
    return $P;
  }

  /**
  * getRowDimension
  * @return int Row dimension
  */
  function getRowDimension() {
    return $this->m;
  }

  /**
  * getColumnDimension
  * @return int Column dimension
  */
  function getColumnDimension() {
    return $this->n;
  }

  /**
  * get
  * Get the i,j-th element of the matrix.
  * @param int $i Row position
  * @param int $j Column position
  * @return mixed Element (int/float/double)
  */
  function get( $i = null, $j = null ) {
    return $this->A[$i][$j];
  }

  /**
  * getMatrix
  * Get a submatrix
  * @param int $i0 Initial row index
  * @param int $iF Final row index
  * @param int $j0 Initial column index
  * @param int $jF Final column index
  * @return Matrix Submatrix
  */
  function getMatrix() {
    if( func_num_args() > 0 ) {
      $args = func_get_args();
      $match = implode(",", array_map('gettype', $args));
      switch( $match ) {

      //A($i0...; $j0...)
      case 'integer,integer':
        list($i0, $j0) = $args;
          $m = $i0 >= 0 ? $this->m - $i0 : trigger_error(ArgumentBoundsException, ERROR);
          $n = $j0 >= 0 ? $this->n - $j0 : trigger_error(ArgumentBoundsException, ERROR);
          $R = new Matrix($m, $n);

          for($i = $i0; $i < $this->m; $i++)
            for($j = $j0; $j < $this->n; $j++)
              $R->set($i, $j, $this->A[$i][$j]);

          return $R;
          break;

      //A($i0...$iF; $j0...$jF)
      case 'integer,integer,integer,integer':
       list($i0, $iF, $j0, $jF) = $args;
        $m = ( ($iF > $i0) && ($this->m >= $iF) && ($i0 >= 0) ) ? $iF - $i0 : trigger_error(ArgumentBoundsException, ERROR);
        $n = ( ($jF > $j0) && ($this->n >= $jF) && ($j0 >= 0)  ) ? $jF - $j0 : trigger_error(ArgumentBoundsException, ERROR);
        $R = new Matrix($m+1, $n+1);

        for($i = $i0; $i <= $iF; $i++)
          for($j = $j0; $j <= $jF; $j++)
            $R->set($i - $i0, $j - $j0, $this->A[$i][$j]);

        return $R;
        break;

      //$R = array of row indices; $C = array of column indices
      case 'array,array':
        list($RL, $CL) = $args;
        $m = count($RL) > 0 ? count($RL) : trigger_error(ArgumentBoundsException, ERROR);
        $n = count($CL) > 0 ? count($CL) : trigger_error(ArgumentBoundsException, ERROR);
        $R = new Matrix($m, $n);

        for($i = 0; $i < $m; $i++)
          for($j = 0; $j < $n; $j++)
            $R->set($i - $i0, $j - $j0, $this->A[$RL[$i]][$CL[$j]]);

        return $R;
        break;

      //$RL = array of row indices; $CL = array of column indices
      case 'array,array':
        list($RL, $CL) = $args;
        $m = count($RL) > 0 ? count($RL) : trigger_error(ArgumentBoundsException, ERROR);
        $n = count($CL) > 0 ? count($CL) : trigger_error(ArgumentBoundsException, ERROR);
        $R = new Matrix($m, $n);

        for($i = 0; $i < $m; $i++)
          for($j = 0; $j < $n; $j++)
            $R->set($i, $j, $this->A[$RL[$i]][$CL[$j]]);

        return $R;
        break;

      //A($i0...$iF); $CL = array of column indices
      case 'integer,integer,array':
        list($i0, $iF, $CL) = $args;
        $m = ( ($iF > $i0) && ($this->m >= $iF) && ($i0 >= 0) ) ? $iF - $i0 : trigger_error(ArgumentBoundsException, ERROR);
        $n = count($CL) > 0 ? count($CL) : trigger_error(ArgumentBoundsException, ERROR);
        $R = new Matrix($m, $n);

        for($i = $i0; $i < $iF; $i++)
          for($j = 0; $j < $n; $j++)
            $R->set($i - $i0, $j, $this->A[$RL[$i]][$j]);

        return $R;
        break;

      //$RL = array of row indices
      case 'array,integer,integer':
        list($RL, $j0, $jF) = $args;
        $m = count($RL) > 0 ? count($RL) : trigger_error(ArgumentBoundsException, ERROR);
        $n = ( ($jF >= $j0) && ($this->n >= $jF) && ($j0 >= 0)  ) ? $jF - $j0 : trigger_error(ArgumentBoundsException, ERROR);
        $R = new Matrix($m, $n+1);

        for($i = 0; $i < $m; $i++)
          for($j = $j0; $j <= $jF; $j++)
            $R->set($i, $j - $j0, $this->A[$RL[$i]][$j]);

        return $R;
        break;
      default:
        trigger_error(PolymorphicArgumentException, ERROR);
        break;
      }
    } else {
      trigger_error(PolymorphicArgumentException, ERROR);
    }
  }

  /**
  * setMatrix
  * Set a submatrix
  * @param int $i0 Initial row index
  * @param int $j0 Initial column index
  * @param mixed $S Matrix/Array submatrix
  * ($i0, $j0, $S) $S = Matrix
  * ($i0, $j0, $S) $S = Array
  */
  function setMatrix( ) {
    if( func_num_args() > 0 ) {
      $args = func_get_args();
      $match = implode(",", array_map('gettype', $args));

      switch( $match ) {
        case 'integer,integer,object':
          $M = is_a($args[2], 'Matrix') ? $args[2] : trigger_error(ArgumentTypeException, ERROR);
          $i0 = ( ($args[0] + $M->m) <= $this->m ) ? $args[0] : trigger_error(ArgumentBoundsException, ERROR);
          $j0 = ( ($args[1] + $M->n) <= $this->n ) ? $args[1] : trigger_error(ArgumentBoundsException, ERROR);

          for($i = $i0; $i < $i0 + $M->m; $i++) {
            for($j = $j0; $j < $j0 + $M->n; $j++) {
              $this->A[$i][$j] = $M->get($i - $i0, $j - $j0);
            }
          }

          break;

        case 'integer,integer,array':
          $M = new Matrix($args[2]);
          $i0 = ( ($args[0] + $M->m) <= $this->m ) ? $args[0] : trigger_error(ArgumentBoundsException, ERROR);
          $j0 = ( ($args[1] + $M->n) <= $this->n ) ? $args[1] : trigger_error(ArgumentBoundsException, ERROR);

          for($i = $i0; $i < $i0 + $M->m; $i++) {
            for($j = $j0; $j < $j0 + $M->n; $j++) {
              $this->A[$i][$j] = $M->get($i - $i0, $j - $j0);
            }
          }

          break;

        default:
          trigger_error(PolymorphicArgumentException, ERROR);
          break;
      }
    } else {
      trigger_error(PolymorphicArgumentException, ERROR);
    }
  }

  /**
  * checkMatrixDimensions
  * Is matrix B the same size?
  * @param Matrix $B Matrix B
  * @return boolean
  */
  function checkMatrixDimensions( $B = null ) {
    if( is_a($B, 'Matrix') )
      if( ($this->m == $B->m) && ($this->n == $B->n) )
        return true;
      else
        trigger_error(MatrixDimensionException, ERROR);

    else
      trigger_error(ArgumentTypeException, ERROR);
  }



  /**
  * set
  * Set the i,j-th element of the matrix.
  * @param int $i Row position
  * @param int $j Column position
  * @param mixed $c Int/float/double value
  * @return mixed Element (int/float/double)
  */
  function set( $i = null, $j = null, $c = null ) {
    // Optimized set version just has this
    $this->A[$i][$j] = $c;
    /*
    if( is_int($i) && is_int($j) && is_numeric($c) ) {
      if( ( $i < $this->m ) && ( $j < $this->n ) ) {
        $this->A[$i][$j] = $c;
      } else {
        echo "A[$i][$j] = $c<br />";
        trigger_error(ArgumentBoundsException, WARNING);
      }
    } else {
      trigger_error(ArgumentTypeException, WARNING);
    }
    */
  }

  /**
  * identity
  * Generate an identity matrix.
  * @param int $m Row dimension
  * @param int $n Column dimension
  * @return Matrix Identity matrix
  */
  function &identity( $m = null, $n = null ) {
    return Matrix::diagonal($m, $n, 1);
  }

  /**
  * diagonal
  * Generate a diagonal matrix
  * @param int $m Row dimension
  * @param int $n Column dimension
  * @param mixed $c Diagonal value
  * @return Matrix Diagonal matrix
  */
  function &diagonal( $m = null, $n = null, $c = 1 ) {
    $R = new Matrix($m, $n);
    for($i = 0; $i < $m; $i++)
      $R->set($i, $i, $c);
    return $R;
  }

  /**
  * filled
  * Generate a filled matrix
  * @param int $m Row dimension
  * @param int $n Column dimension
  * @param int $c Fill constant
  * @return Matrix Filled matrix
  */
  function &filled( $m = null, $n = null, $c = 0 ) {
    if( is_int($m) && is_int($n) && is_numeric($c) ) {
      $R = new Matrix($m, $n, $c);
      return $R;
    } else {
      trigger_error(ArgumentTypeException, ERROR);
    }
  }

  /**
  * random
  * Generate a random matrix
  * @param int $m Row dimension
  * @param int $n Column dimension
  * @return Matrix Random matrix
  */
  function &random( $m = null, $n = null, $a = RAND_MIN, $b = RAND_MAX ) {
    if( is_int($m) && is_int($n) && is_numeric($a) && is_numeric($b) ) {
      $R = new Matrix($m, $n);

      for($i = 0; $i < $m; $i++)
        for($j = 0; $j < $n; $j++)
          $R->set($i, $j, mt_rand($a, $b));

      return $R;
    } else {
      trigger_error(ArgumentTypeException, ERROR);
    }
  }

  /**
  * packed
  * Alias for getRowPacked
  * @return array Packed array
  */
  function &packed() {
    return $this->getRowPacked();
  }


  /**
  * getMatrixByRow
  * Get a submatrix by row index/range
  * @param int $i0 Initial row index
  * @param int $iF Final row index
  * @return Matrix Submatrix
  */
  function getMatrixByRow( $i0 = null, $iF = null ) {
    if( is_int($i0) ) {
      if( is_int($iF) )
        return $this->getMatrix($i0, 0, $iF + 1, $this->n);
      else
        return $this->getMatrix($i0, 0, $i0 + 1, $this->n);
    } else
      trigger_error(ArgumentTypeException, ERROR);
  }

  /**
  * getMatrixByCol
  * Get a submatrix by column index/range
  * @param int $i0 Initial column index
  * @param int $iF Final column index
  * @return Matrix Submatrix
  */
  function getMatrixByCol( $j0 = null, $jF = null ) {
    if( is_int($j0) ) {
      if( is_int($jF) )
        return $this->getMatrix(0, $j0, $this->m, $jF + 1);
      else
        return $this->getMatrix(0, $j0, $this->m, $j0 + 1);
    } else
      trigger_error(ArgumentTypeException, ERROR);
  }

  /**
  * transpose
  * Tranpose matrix
  * @return Matrix Transposed matrix
  */
  function transpose() {
    $R = new Matrix($this->n, $this->m);

    for($i = 0; $i < $this->m; $i++)
      for($j = 0; $j < $this->n; $j++)
        $R->set($j, $i, $this->A[$i][$j]);

    return $R;
  }

/*
   public Matrix transpose () {
      Matrix X = new Matrix(n,m);
      double[][] C = X.getArray();
      for (int i = 0; i < m; i++) {
         for (int j = 0; j < n; j++) {
            C[j][i] = A[i][j];
         }
      }
      return X;
   }
*/

  /**
  * norm1
  * One norm
  * @return float Maximum column sum
  */
  function norm1() {
    $r = 0;

    for($j = 0; $j < $this->n; $j++) {
      $s = 0;

      for($i = 0; $i < $this->m; $i++) {
        $s += abs($this->A[$i][$j]);
      }

      $r = ( $r > $s ) ? $r : $s;
    }

    return $r;
  }


  /**
  * norm2
  * Maximum singular value
  * @return float Maximum singular value
  */
  function norm2() {

  }

  /**
  * normInf
  * Infinite norm
  * @return float Maximum row sum
  */
  function normInf() {
    $r = 0;

    for($i = 0; $i < $this->m; $i++) {
      $s = 0;

      for($j = 0; $j < $this->n; $j++) {
        $s += abs($this->A[$i][$j]);
      }

      $r = ( $r > $s ) ? $r : $s;
    }

    return $r;
  }

  /**
  * normF
  * Frobenius norm
  * @return float Square root of the sum of all elements squared
  */
  function normF() {
	  $f = 0;
    for ($i = 0; $i < $this->m; $i++)
      for ($j = 0; $j < $this->n; $j++)
        $f = hypo($f,$this->A[$i][$j]);
    return $f;
  }

  /**
  * Matrix rank
  * @return effective numerical rank, obtained from SVD.
  */
  function rank () {
    $svd = new SingularValueDecomposition($this);
    return $svd->rank();
  }

  /**
  * Matrix condition (2 norm)
  * @return ratio of largest to smallest singular value.
  */
  function cond () {
    $svd = new SingularValueDecomposition($this);
    return $svd->cond();
  }

  /**
  * trace
  * Sum of diagonal elements
  * @return float Sum of diagonal elements
  */
  function trace() {
    $s = 0;
	  $n = min($this->m, $this->n);

    for($i = 0; $i < $n; $i++)
      $s += $this->A[$i][$i];

    return $s;
  }


  /**
  * uminus
  * Unary minus matrix -A
  * @return Matrix Unary minus matrix
  */
  function uminus() {

  }

  /**
  * plus
  * A + B
  * @param mixed $B Matrix/Array
  * @return Matrix Sum
  */
  function plus() {
    if( func_num_args() > 0 ) {
      $args = func_get_args();
      $match = implode(",", array_map('gettype', $args));

      switch( $match ) {
        case 'object':
          $M = is_a($args[0], 'Matrix') ? $args[0] : trigger_error(ArgumentTypeException, ERROR);
          //$this->checkMatrixDimensions($M);

          for($i = 0; $i < $this->m; $i++) {
            for($j = 0; $j < $this->n; $j++) {
              $M->set($i, $j, $M->get($i, $j) + $this->A[$i][$j]);
            }
          }

          return $M;
          break;

        case 'array':
          $M = new Matrix($args[0]);
          //$this->checkMatrixDimensions($M);

          for($i = 0; $i < $this->m; $i++) {
            for($j = 0; $j < $this->n; $j++) {
              $M->set($i, $j, $M->get($i, $j) + $this->A[$i][$j]);
            }
          }

          return $M;
          break;

        default:
          trigger_error(PolymorphicArgumentException, ERROR);
          break;
      }
    } else {
      trigger_error(PolymorphicArgumentException, ERROR);
    }
  }

  /**
  * plusEquals
  * A = A + B
  * @param mixed $B Matrix/Array
  * @return Matrix Sum
  */
  function &plusEquals() {
    if( func_num_args() > 0 ) {
      $args = func_get_args();
      $match = implode(",", array_map('gettype', $args));

      switch( $match ) {
        case 'object':
          $M = is_a($args[0], 'Matrix') ? $args[0] : trigger_error(ArgumentTypeException, ERROR);
          $this->checkMatrixDimensions($M);

          for($i = 0; $i < $this->m; $i++) {
            for($j = 0; $j < $this->n; $j++) {
              $this->A[$i][$j] += $M->get($i, $j);
            }
          }

          return $this;
          break;

        case 'array':
          $M = new Matrix($args[0]);
          $this->checkMatrixDimensions($M);

          for($i = 0; $i < $this->m; $i++) {
            for($j = 0; $j < $this->n; $j++) {
              $this->A[$i][$j] += $M->get($i, $j);
            }
          }

          return $this;
          break;

        default:
          trigger_error(PolymorphicArgumentException, ERROR);
          break;
      }
    } else {
      trigger_error(PolymorphicArgumentException, ERROR);
    }
  }

  /**
  * minus
  * A - B
  * @param mixed $B Matrix/Array
  * @return Matrix Sum
  */
  function minus() {
    if( func_num_args() > 0 ) {
      $args = func_get_args();
      $match = implode(",", array_map('gettype', $args));

      switch( $match ) {
        case 'object':
          $M = is_a($args[0], 'Matrix') ? $args[0] : trigger_error(ArgumentTypeException, ERROR);
          $this->checkMatrixDimensions($M);

          for($i = 0; $i < $this->m; $i++) {
            for($j = 0; $j < $this->n; $j++) {
              $M->set($i, $j, $M->get($i, $j) - $this->A[$i][$j]);
            }
          }

          return $M;
          break;

        case 'array':
          $M = new Matrix($args[0]);
          $this->checkMatrixDimensions($M);

          for($i = 0; $i < $this->m; $i++) {
            for($j = 0; $j < $this->n; $j++) {
              $M->set($i, $j, $M->get($i, $j) - $this->A[$i][$j]);
            }
          }

          return $M;
          break;

        default:
          trigger_error(PolymorphicArgumentException, ERROR);
          break;
      }
    } else {
      trigger_error(PolymorphicArgumentException, ERROR);
    }
  }

  /**
  * minusEquals
  * A = A - B
  * @param mixed $B Matrix/Array
  * @return Matrix Sum
  */
  function &minusEquals() {
    if( func_num_args() > 0 ) {
      $args = func_get_args();
      $match = implode(",", array_map('gettype', $args));

      switch( $match ) {
        case 'object':
          $M = is_a($args[0], 'Matrix') ? $args[0] : trigger_error(ArgumentTypeException, ERROR);
          $this->checkMatrixDimensions($M);

          for($i = 0; $i < $this->m; $i++) {
            for($j = 0; $j < $this->n; $j++) {
              $this->A[$i][$j] -= $M->get($i, $j);
            }
          }

          return $this;
          break;

        case 'array':
          $M = new Matrix($args[0]);
          $this->checkMatrixDimensions($M);

          for($i = 0; $i < $this->m; $i++) {
            for($j = 0; $j < $this->n; $j++) {
              $this->A[$i][$j] -= $M->get($i, $j);
            }
          }

          return $this;
          break;

        default:
          trigger_error(PolymorphicArgumentException, ERROR);
          break;
      }
    } else {
      trigger_error(PolymorphicArgumentException, ERROR);
    }
  }

  /**
  * arrayTimes
  * Element-by-element multiplication
  * Cij = Aij * Bij
  * @param mixed $B Matrix/Array
  * @return Matrix Matrix Cij
  */
  function arrayTimes() {
    if( func_num_args() > 0 ) {
      $args = func_get_args();
      $match = implode(",", array_map('gettype', $args));

      switch( $match ) {
        case 'object':
          $M = is_a($args[0], 'Matrix') ? $args[0] : trigger_error(ArgumentTypeException, ERROR);
          $this->checkMatrixDimensions($M);

          for($i = 0; $i < $this->m; $i++) {
            for($j = 0; $j < $this->n; $j++) {
              $M->set($i, $j, $M->get($i, $j) * $this->A[$i][$j]);
            }
          }

          return $M;
          break;

        case 'array':
          $M = new Matrix($args[0]);
          $this->checkMatrixDimensions($M);

          for($i = 0; $i < $this->m; $i++) {
            for($j = 0; $j < $this->n; $j++) {
              $M->set($i, $j, $M->get($i, $j) * $this->A[$i][$j]);
            }
          }

          return $M;
          break;

        default:
          trigger_error(PolymorphicArgumentException, ERROR);
          break;
      }
    } else {
      trigger_error(PolymorphicArgumentException, ERROR);
    }
  }


  /**
  * arrayTimesEquals
  * Element-by-element multiplication
  * Aij = Aij * Bij
  * @param mixed $B Matrix/Array
  * @return Matrix Matrix Aij
  */
  function &arrayTimesEquals() {
    if( func_num_args() > 0 ) {
      $args = func_get_args();
      $match = implode(",", array_map('gettype', $args));

      switch( $match ) {
        case 'object':
          $M = is_a($args[0], 'Matrix') ? $args[0] : trigger_error(ArgumentTypeException, ERROR);
          $this->checkMatrixDimensions($M);

          for($i = 0; $i < $this->m; $i++) {
            for($j = 0; $j < $this->n; $j++) {
              $this->A[$i][$j] *= $M->get($i, $j);
            }
          }

          return $this;
          break;

        case 'array':
          $M = new Matrix($args[0]);
          $this->checkMatrixDimensions($M);

          for($i = 0; $i < $this->m; $i++) {
            for($j = 0; $j < $this->n; $j++) {
              $this->A[$i][$j] *= $M->get($i, $j);
            }
          }

          return $this;
          break;

        default:
          trigger_error(PolymorphicArgumentException, ERROR);
          break;
      }
    } else {
      trigger_error(PolymorphicArgumentException, ERROR);
    }
  }

  /**
  * arrayRightDivide
  * Element-by-element right division
  * A / B
  * @param Matrix $B Matrix B
  * @return Matrix Division result
  */
  function arrayRightDivide() {
    if( func_num_args() > 0 ) {
      $args = func_get_args();
      $match = implode(",", array_map('gettype', $args));

      switch( $match ) {
        case 'object':
          $M = is_a($args[0], 'Matrix') ? $args[0] : trigger_error(ArgumentTypeException, ERROR);
          $this->checkMatrixDimensions($M);

          for($i = 0; $i < $this->m; $i++) {
            for($j = 0; $j < $this->n; $j++) {
              $M->set($i, $j, $this->A[$i][$j] / $M->get($i, $j) );
            }
          }

          return $M;
          break;

        case 'array':
          $M = new Matrix($args[0]);
          $this->checkMatrixDimensions($M);

          for($i = 0; $i < $this->m; $i++) {
            for($j = 0; $j < $this->n; $j++) {
              $M->set($i, $j, $this->A[$i][$j] / $M->get($i, $j));
            }
          }

          return $M;
          break;

        default:
          trigger_error(PolymorphicArgumentException, ERROR);
          break;
      }
    } else {
      trigger_error(PolymorphicArgumentException, ERROR);
    }
  }

  /**
  * arrayRightDivideEquals
  * Element-by-element right division
  * Aij = Aij / Bij
  * @param mixed $B Matrix/Array
  * @return Matrix Matrix Aij
  */
  function &arrayRightDivideEquals() {
    if( func_num_args() > 0 ) {
      $args = func_get_args();
      $match = implode(",", array_map('gettype', $args));

      switch( $match ) {
        case 'object':
          $M = is_a($args[0], 'Matrix') ? $args[0] : trigger_error(ArgumentTypeException, ERROR);
          $this->checkMatrixDimensions($M);

          for($i = 0; $i < $this->m; $i++) {
            for($j = 0; $j < $this->n; $j++) {
              $this->A[$i][$j] = $this->A[$i][$j] / $M->get($i, $j);
            }
          }

          return $M;
          break;

        case 'array':
          $M = new Matrix($args[0]);
          $this->checkMatrixDimensions($M);

          for($i = 0; $i < $this->m; $i++) {
            for($j = 0; $j < $this->n; $j++) {
              $this->A[$i][$j] = $this->A[$i][$j] / $M->get($i, $j);
            }
          }

          return $M;
          break;

        default:
          trigger_error(PolymorphicArgumentException, ERROR);
          break;
      }
    } else {
      trigger_error(PolymorphicArgumentException, ERROR);
    }
  }

  /**
  * arrayLeftDivide
  * Element-by-element Left division
  * A / B
  * @param Matrix $B Matrix B
  * @return Matrix Division result
  */
  function arrayLeftDivide() {
    if( func_num_args() > 0 ) {
      $args = func_get_args();
      $match = implode(",", array_map('gettype', $args));

      switch( $match ) {
        case 'object':
          $M = is_a($args[0], 'Matrix') ? $args[0] : trigger_error(ArgumentTypeException, ERROR);
          $this->checkMatrixDimensions($M);

          for($i = 0; $i < $this->m; $i++) {
            for($j = 0; $j < $this->n; $j++) {
              $M->set($i, $j, $M->get($i, $j) / $this->A[$i][$j] );
            }
          }

          return $M;
          break;

        case 'array':
          $M = new Matrix($args[0]);
          $this->checkMatrixDimensions($M);

          for($i = 0; $i < $this->m; $i++) {
            for($j = 0; $j < $this->n; $j++) {
              $M->set($i, $j, $M->get($i, $j) / $this->A[$i][$j] );
            }
          }

          return $M;
          break;

        default:
          trigger_error(PolymorphicArgumentException, ERROR);
          break;
      }
    } else {
      trigger_error(PolymorphicArgumentException, ERROR);
    }
  }

  /**
  * arrayLeftDivideEquals
  * Element-by-element Left division
  * Aij = Aij / Bij
  * @param mixed $B Matrix/Array
  * @return Matrix Matrix Aij
  */
  function &arrayLeftDivideEquals() {
    if( func_num_args() > 0 ) {
      $args = func_get_args();
      $match = implode(",", array_map('gettype', $args));

      switch( $match ) {
        case 'object':
          $M = is_a($args[0], 'Matrix') ? $args[0] : trigger_error(ArgumentTypeException, ERROR);
          $this->checkMatrixDimensions($M);

          for($i = 0; $i < $this->m; $i++) {
            for($j = 0; $j < $this->n; $j++) {
              $this->A[$i][$j] = $M->get($i, $j) / $this->A[$i][$j];
            }
          }

          return $M;
          break;

        case 'array':
          $M = new Matrix($args[0]);
          $this->checkMatrixDimensions($M);

          for($i = 0; $i < $this->m; $i++) {
            for($j = 0; $j < $this->n; $j++) {
              $this->A[$i][$j] = $M->get($i, $j) / $this->A[$i][$j];
            }
          }

          return $M;
          break;

        default:
          trigger_error(PolymorphicArgumentException, ERROR);
          break;
      }
    } else {
      trigger_error(PolymorphicArgumentException, ERROR);
    }
  }

  /**
  * times
  * Matrix multiplication
  * @param mixed $n Matrix/Array/Scalar
  * @return Matrix Product
  */
  function times() {
    if(func_num_args() > 0) {
      $args  = func_get_args();
      $match = implode(",", array_map('gettype', $args));
      switch($match) {
          case 'object':
            $B = is_a($args[0], 'Matrix') ? $args[0] : trigger_error(ArgumentTypeException, ERROR);
            if($this->n == $B->m) {
              $C = new Matrix($this->m, $B->n);
              for($j = 0; $j < $B->n; $j++ ) {
                for ($k = 0; $k < $this->n; $k++)
                  $Bcolj[$k] = $B->A[$k][$j];
                for($i = 0; $i < $this->m; $i++ ) {
                  $Arowi = $this->A[$i];
                  $s = 0;
                  for( $k = 0; $k < $this->n; $k++ )
                    $s += $Arowi[$k] * $Bcolj[$k];
                  $C->A[$i][$j] = $s;
                }
              }
              return $C;
            } else
              trigger_error(MatrixDimensionMismatch, FATAL);
            break;

          case 'array':
            $B = new Matrix($args[0]);
            if($this->n == $B->m) {
              $C = new Matrix($this->m, $B->n);
              for($i = 0; $i < $C->m; $i++) {
                for($j = 0; $j < $C->n; $j++) {
                  $s = "0";
                  for($k = 0; $k < $C->n; $k++)
                    $s += $this->A[$i][$k] * $B->A[$k][$j];
                  $C->A[$i][$j] = $s;
                }
              }
              return $C;
            } else
              trigger_error(MatrixDimensionMismatch, FATAL);
            return $M;
            break;
          case 'integer':
            $C = new Matrix($this->A);
            for($i = 0; $i < $C->m; $i++)
              for($j = 0; $j < $C->n; $j++)
                $C->A[$i][$j] *= $args[0];
            return $C;
            break;
          case 'double':
            $C = new Matrix($this->m, $this->n);
            for($i = 0; $i < $C->m; $i++)
              for($j = 0; $j < $C->n; $j++)
                  $C->A[$i][$j] = $args[0] * $this->A[$i][$j];
            return $C;
            break;
          case 'float':
            $C = new Matrix($this->A);
            for($i = 0; $i < $C->m; $i++)
              for($j = 0; $j < $C->n; $j++)
                $C->A[$i][$j] *= $args[0];
            return $C;
            break;
          default:
            trigger_error(PolymorphicArgumentException, ERROR);
            break;
      }
    } else
      trigger_error(PolymorphicArgumentException, ERROR);
  }

  /**
  * chol
  * Cholesky decomposition
  * @return Matrix Cholesky decomposition
  */
  function chol() {
    return new CholeskyDecomposition($this);
  }

  /**
  * lu
  * LU decomposition
  * @return Matrix LU decomposition
  */
  function lu() {
    return new LUDecomposition($this);
  }

  /**
  * qr
  * QR decomposition
  * @return Matrix QR decomposition
  */
  function qr() {
    return new QRDecomposition($this);
  }


  /**
  * eig
  * Eigenvalue decomposition
  * @return Matrix Eigenvalue decomposition
  */
  function eig() {
    return new EigenvalueDecomposition($this);
  }

  /**
  * svd
  * Singular value decomposition
  * @return Singular value decomposition
  */
  function svd() {
    return new SingularValueDecomposition($this);
  }

  /**
  * Solve A*X = B.
  * @param Matrix $B Right hand side
  * @return Matrix ... Solution if A is square, least squares solution otherwise
  */
  function solve($B) {
    if ($this->m == $this->n) {
      $LU = new LUDecomposition($this);
      return $LU->solve($B);
    } else {
      $QR = new QRDecomposition($this);
      return $QR->solve($B);
    }
  }

  /**
  * Matrix inverse or pseudoinverse.
  * @return Matrix ... Inverse(A) if A is square, pseudoinverse otherwise.
  */
  function inverse() {
    return $this->solve($this->identity($this->m, $this->m));
  }


  /**
  * det
  * Calculate determinant
  * @return float Determinant
  */
  function det() {
    $L = new LUDecomposition($this);
    return $L->det();
  }

   /**
  * Older debugging utility for backwards compatability.
  * @return html version of matrix
  */
  function mprint($A, $format="%01.2f", $width=2) {
    $spacing = "";
    $m = count($A);
    $n = count($A[0]);
    for($i = 0; $i < $width; $i++)
       $spacing .= "&nbsp;";
    for ($i = 0; $i < $m; $i++) {
      for ($j = 0; $j < $n; $j++) {
        $formatted = sprintf($format, $A[$i][$j]);
        echo $formatted . $spacing;
      }
      echo "<br />";
    }
  }

  /**
  * Debugging utility.
  * @return Output HTML representation of matrix
  */
  function toHTML($width=2) {
    print( '<table style="background-color:#eee;">');
    for( $i = 0; $i < $this->m; $i++ ) {
      print( '<tr>' );
      for( $j = 0; $j < $this->n; $j++ )
        print( '<td style="background-color:#fff;border:1px solid #000;padding:2px;text-align:center;vertical-align:middle;">' . $this->A[$i][$j] . '</td>' );
      print( '</tr>');
    }
    print( '</table>' );
  }
}

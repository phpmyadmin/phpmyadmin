<?php
/**
* @package JAMA
*
* For an m-by-n matrix A with m >= n, the LU decomposition is an m-by-n
* unit lower triangular matrix L, an n-by-n upper triangular matrix U,
* and a permutation vector piv of length m so that A(piv,:) = L*U.
* If m < n, then L is m-by-m and U is m-by-n.
*
* The LU decompostion with pivoting always exists, even if the matrix is
* singular, so the constructor will never fail.  The primary use of the
* LU decomposition is in the solution of square systems of simultaneous
* linear equations.  This will fail if isNonsingular() returns false.
*
* @author Paul Meagher
* @author Bartosz Matosiuk
* @author Michael Bommarito
* @version 1.1
* @license PHP v3.0
*/
class LUDecomposition {
  /**
  * Decomposition storage
  * @var array
  */
  var $LU = array();
  
  /**
  * Row dimension.
  * @var int  
  */
  var $m;

  /**
  * Column dimension.
  * @var int    
  */   
  var $n;
  
  /**
  * Pivot sign.
  * @var int    
  */      
  var $pivsign;

  /**
  * Internal storage of pivot vector.
  * @var array  
  */
  var $piv = array();
  
  /**
  * LU Decomposition constructor.
  * @param $A Rectangular matrix
  * @return Structure to access L, U and piv.
  */
  function LUDecomposition ($A) {   
    if( is_a($A, 'Matrix') ) {
    // Use a "left-looking", dot-product, Crout/Doolittle algorithm.
    $this->LU = $A->getArrayCopy();
    $this->m  = $A->getRowDimension();
    $this->n  = $A->getColumnDimension();
    for ($i = 0; $i < $this->m; $i++)
      $this->piv[$i] = $i;
    $this->pivsign = 1;   
    $LUrowi = array();
    $LUcolj = array();
    // Outer loop.
    for ($j = 0; $j < $this->n; $j++) {
      // Make a copy of the j-th column to localize references.
      for ($i = 0; $i < $this->m; $i++)
        $LUcolj[$i] = &$this->LU[$i][$j];
      // Apply previous transformations.
      for ($i = 0; $i < $this->m; $i++) {        
        $LUrowi = $this->LU[$i];        
        // Most of the time is spent in the following dot product.
        $kmax = min($i,$j);
        $s = 0.0;
        for ($k = 0; $k < $kmax; $k++)
          $s += $LUrowi[$k]*$LUcolj[$k];
          $LUrowi[$j] = $LUcolj[$i] -= $s;                                                  
      }       
      // Find pivot and exchange if necessary.
      $p = $j;
      for ($i = $j+1; $i < $this->m; $i++) {
      if (abs($LUcolj[$i]) > abs($LUcolj[$p]))
        $p = $i;
      }
      if ($p != $j) {
      for ($k = 0; $k < $this->n; $k++) {                
        $t = $this->LU[$p][$k];                              
        $this->LU[$p][$k] = $this->LU[$j][$k];                              
        $this->LU[$j][$k] = $t;                              
      }
      $k = $this->piv[$p];
      $this->piv[$p] = $this->piv[$j];
      $this->piv[$j] = $k;
      $this->pivsign = $this->pivsign * -1;
      }
      // Compute multipliers.     
      if ( ($j < $this->m) AND ($this->LU[$j][$j] != 0.0) ) {
      for ($i = $j+1; $i < $this->m; $i++)
        $this->LU[$i][$j] /= $this->LU[$j][$j];               
      }      
    }
    } else {
      trigger_error(ArgumentTypeException, ERROR);
    }
  }
  
  /**
  * Get lower triangular factor.
  * @return array Lower triangular factor
  */
  function getL () {
    for ($i = 0; $i < $this->m; $i++) {
      for ($j = 0; $j < $this->n; $j++) {
        if ($i > $j)
          $L[$i][$j] = $this->LU[$i][$j];
        else if($i == $j)
          $L[$i][$j] = 1.0;
        else
          $L[$i][$j] = 0.0;
      }
    }
    return new Matrix($L);
  }

  /**
  * Get upper triangular factor.
  * @return array Upper triangular factor
  */  
  function getU () {
    for ($i = 0; $i < $this->n; $i++) {
      for ($j = 0; $j < $this->n; $j++) {
        if ($i <= $j)
          $U[$i][$j] = $this->LU[$i][$j];
        else
          $U[$i][$j] = 0.0;
      }
    }
    return new Matrix($U);
  }
  
  /**
  * Return pivot permutation vector.
  * @return array Pivot vector
  */
  function getPivot () {
     return $this->piv;
  }
  
  /**
  * Alias for getPivot
  * @see getPivot
  */
  function getDoublePivot () {
     return $this->getPivot();
  }

  /**
  * Is the matrix nonsingular?
  * @return true if U, and hence A, is nonsingular.
  */
  function isNonsingular () {
    for ($j = 0; $j < $this->n; $j++) {
      if ($this->LU[$j][$j] == 0)
        return false;
    }
    return true;
  }

  /**
  * Count determinants
  * @return array d matrix deterninat
  */
  function det() {
    if ($this->m == $this->n) {
      $d = $this->pivsign;      
      for ($j = 0; $j < $this->n; $j++)
        $d *= $this->LU[$j][$j];            
      return $d;
    } else {
      trigger_error(MatrixDimensionException, ERROR);
    }
  }
  
  /**
  * Solve A*X = B
  * @param  $B  A Matrix with as many rows as A and any number of columns.
  * @return  X so that L*U*X = B(piv,:)
  * @exception  IllegalArgumentException Matrix row dimensions must agree.
  * @exception  RuntimeException  Matrix is singular.
  */
  function solve($B) {          
    if ($B->getRowDimension() == $this->m) {
      if ($this->isNonsingular()) {        
        // Copy right hand side with pivoting
        $nx = $B->getColumnDimension();
        $X  = $B->getMatrix($this->piv, 0, $nx-1);
        // Solve L*Y = B(piv,:)
        for ($k = 0; $k < $this->n; $k++)
          for ($i = $k+1; $i < $this->n; $i++)
            for ($j = 0; $j < $nx; $j++)
              $X->A[$i][$j] -= $X->A[$k][$j] * $this->LU[$i][$k];
        // Solve U*X = Y;
        for ($k = $this->n-1; $k >= 0; $k--) {
          for ($j = 0; $j < $nx; $j++)
            $X->A[$k][$j] /= $this->LU[$k][$k];
          for ($i = 0; $i < $k; $i++)
            for ($j = 0; $j < $nx; $j++)
              $X->A[$i][$j] -= $X->A[$k][$j] * $this->LU[$i][$k];
        }
        return $X;
      } else {
        trigger_error(MatrixSingularException, ERROR);
      }
    } else {
      trigger_error(MatrixSquareException, ERROR);
    }
  }   
}

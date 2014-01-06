<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Class for a sniff to oblige whitespaces before and after a concatenation operator
 *
 * This Sniff check if a whitespace is missing before or after any concatenation operator.
 * The concatenation operator is identified by the PHP token T_STRING_CONCAT
 * The whitespace is identified by the PHP token T_WHITESPACE
 *
 * PHP version 5
 *
 * LICENSE: CC-BY-SA
 * - The licensor permits others to copy, distribute, display, and perform the work. In return, licenses must give the
 * original author credit.
 * - The licensor permits others to distribute derivative works only under a license identical to the one that governs
 * the licensor's work
 * - The licensor strongly encourages modifications on this software
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESSED OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE PHP DEVELOPMENT TEAM OR ITS CONTRIBUTORS OR ME BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category    PHP
 * @package     PHP_CodeSniffer
 * @author      Nicolas Giraud
 * @since       September, the 12th 2013
 */

/// {{{ Self_Sniffs_Formatting_SpacesAroundConcatSniff
/**
 * Sniff to oblige whitespaces before and after a concatenation operator
 *
 * @category    PHP
 * @package     PHP_CodeSniffer
 * @subpackage  PMAStandard
 * @name        SpacesAroundConcatSniff
 * @author      Nicolas Giraud
 * @since       September, the 12th 2013
 * @version     Release: 1.0
 */
class PMAStandard_Sniffs_Files_SpacesAroundConcatSniff implements PHP_CodeSniffer_Sniff
{

    // {{{ register()

    /**
     * Returns the token types that this sniff is interested in.
     *
     * @name register
     * @access public
     * @see PHP_CodeSniffer_Sniff::register()
     *
     * @return array(int)
     */
    public function register()
    {
        return array(T_STRING_CONCAT);

    }//end register()

    // }}}
    // {{{ process()

    /**
     * Processes the tokens that this sniff is interested in.
     *
     * @name process
     * @access public
     * @see PHP_CodeSniffer_Sniff::process()
     * @param PHP_CodeSniffer_File $phpcsFile The file where the token was found.
     * @param int                  $stackPtr  The position in the stack where the token was found.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr-1]['type'] !== 'T_WHITESPACE') {
            $warning = 'Whitespace is expected before any concat operator "."';
            $phpcsFile->addWarning($warning, $stackPtr, 'Found');
        }
        if ($tokens[$stackPtr+1]['type'] !== 'T_WHITESPACE') {
            $warning = 'Whitespace is expected after any concat operator "."';
            $phpcsFile->addWarning($warning, $stackPtr, 'Found');
        }
    }//end process()

    // }}}

}//end class

// }}}

?>

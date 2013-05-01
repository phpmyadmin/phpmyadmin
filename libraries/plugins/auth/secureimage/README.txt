NAME:

    Securimage - A PHP class for creating captcha images and audio with many options.

VERSION: 3.5

AUTHOR:

    Drew Phillips <drew@drew-phillips.com>

DOWNLOAD:

    The latest version can always be
    found at http://www.phpcaptcha.org

DOCUMENTATION:

    Online documentation of the class, methods, and variables can
    be found at http://www.phpcaptcha.org/Securimage_Docs/

REQUIREMENTS:
    PHP 5.2 or greater
    GD  2.0
    FreeType (Required, for TTF fonts)
    PDO (if using Sqlite, MySQL, or PostgreSQL)

SYNOPSIS:

    require_once 'securimage.php';

    $image = new Securimage();

    $image->show();

    // Code Validation

    $image = new Securimage();
    if ($image->check($_POST['code']) == true) {
      echo "Correct!";
    } else {
      echo "Sorry, wrong code.";
    }

DESCRIPTION:

    What is Securimage?

    Securimage is a PHP class that is used to generate and validate CAPTCHA images.
    The classes uses an existing PHP session or creates its own if none is found to store the
    CAPTCHA code.  Variables within the class are used to control the style and display of the image.
    The class supports TTF fonts and effects for strengthening the security of the image.
    An audible code can also be streamed to the browser for visually impared users.


COPYRIGHT:
    Copyright (c) 2013 Drew Phillips
    All rights reserved.

    Redistribution and use in source and binary forms, with or without modification,
    are permitted provided that the following conditions are met:

    - Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    - Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
    AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
    IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
    ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
    LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
    CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
    POSSIBILITY OF SUCH DAMAGE.

    -----------------------------------------------------------------------------
    The WavFile.php class used in Securimage by Drew Phillips and Paul Voegler is
    used under the BSD License.  See WavFile.php for details.
    Many thanks to Paul Voegler (http://www.voegler.eu/) for contributing to
    Securimage.

    -----------------------------------------------------------------------------
    Flash code created for Securimage by Age Bosma & Mario Romero (animario@hotmail.com)
    Many thanks for releasing this to the project!

    ------------------------------------------------------------------------------
    Portions of Securimage contain code from Han-Kwang Nienhuys' PHP captcha
        
    Han-Kwang Nienhuys' PHP captcha
    Copyright June 2007
    
    This copyright message and attribution must be preserved upon
    modification. Redistribution under other licenses is expressly allowed.
    Other licenses include GPL 2 or higher, BSD, and non-free licenses.
    The original, unrestricted version can be obtained from
    http://www.lagom.nl/linux/hkcaptcha/
    
    -------------------------------------------------------------------------------
    AHGBold.ttf (AlteHaasGroteskBold.ttf) font was created by Yann Le Coroller and is distributed as freeware
    
    Alte Haas Grotesk is a typeface that look like an helvetica printed in an old Muller-Brockmann Book.
    
    These fonts are freeware and can be distributed as long as they are
    together with this text file. 
    
    I would appreciate very much to see what you have done with it anyway.
    
    yann le coroller 
    www.yannlecoroller.com
    yann@lecoroller.com

    -------------------------------------------------------------------------------
    Portions of securimage_play.swf use the PopForge flash library for playing audio

    /**
     * Copyright(C) 2007 Andre Michelle and Joa Ebert
     *
     * PopForge is an ActionScript3 code sandbox developed by Andre Michelle and Joa Ebert
     * http://sandbox.popforge.de
     *
     * PopforgeAS3Audio is free software; you can redistribute it and/or modify
     * it under the terms of the GNU General Public License as published by
     * the Free Software Foundation; either version 3 of the License, or
     * (at your option) any later version.
     *
     * PopforgeAS3Audio is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
     * GNU General Public License for more details.
     *
     * You should have received a copy of the GNU General Public License
     * along with this program. If not, see <http://www.gnu.org/licenses/>
     */
     
     -------------------------------------------------------------------------------
     Some graphics used are from the Humility Icon Pack by WorLord

     License: GNU/GPL (http://findicons.com/pack/1723/humility)
     http://findicons.com/icon/192558/gnome_volume_control
     http://findicons.com/icon/192562/gtk_refresh

     -------------------------------------------------------------------------------
     Background noise sound files are from SoundJay.com
     http://www.soundjay.com/tos.html
     
     All sound effects on this website are created by us and protected under
     the copyright laws, international treaty provisions and other applicable
     laws. By downloading sounds, music or any material from this site implies
     that you have read and accepted these terms and conditions:

     Sound Effects
     You are allowed to use the sounds free of charge and royalty free in your
     projects (such as films, videos, games, presentations, animations, stage
     plays, radio plays, audio books, apps) be it for commercial or
     non-commercial purposes.
    
     But you are NOT allowed to
     - post the sounds (as sound effects or ringtones) on any website for
       others to download, copy or use
     - use them as a raw material to create sound effects or ringtones that
       you will sell, distribute or offer for downloading
     - sell, re-sell, license or re-license the sounds (as individual sound
       effects or as a sound effects library) to anyone else
     - claim the sounds as yours
     - link directly to individual sound files
     - distribute the sounds in apps or computer programs that are clearly
       sound related in nature (such as sound machine, sound effect
       generator, ringtone maker, funny sounds app, sound therapy app, etc.)
       or in apps or computer programs that use the sounds as the program's
       sound resource library for other people's use (such as animation
       creator, digital book creator, song maker software, etc.). If you are
       developing such computer programs, contact us for licensing options.
    
     If you use the sound effects, please consider giving us a credit and
     linking back to us but it's not required.
     
     
 /*
     pMap - a JavaScript to add image map support to pChart graphs!
     Copyright (C) 2008 Jean-Damien POGOLOTTI
     Version  1.1 last updated on 08/20/08

     http://pchart.sourceforge.net

     This program is free software: you can redistribute it and/or modify
     it under the terms of the GNU General Public License as published by
     the Free Software Foundation, either version 1,2,3 of the License, or
     (at your option) any later version.

     This program is distributed in the hope that it will be useful,
     but WITHOUT ANY WARRANTY; without even the implied warranty of
     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     GNU General Public License for more details.

     You should have received a copy of the GNU General Public License
     along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

 var pMap_IE            = document.all?true:false;
 var pMap_ImageMap      = new Array();
 var pMap_ImageID       = "";
 var pMap_MouseX        = 0;
 var pMap_MouseY        = 0;
 var pMap_CurrentKey    = -1;
 var pMap_URL           = "";
 var pMap_Tries         = 0;
 var pMap_MaxTries      = 5;
 var pMap_HTTP_Timeout  = 1000;
 var pMap_MUTEX         = false;
 var pMap_MUTEX_Timeout = 100;

 if (!pMap_IE) { document.captureEvents(Event.MOUSEMOVE); }

 function getMousePosition(e)
  {
   /* Protect against event storm */
   if (pMap_MUTEX) { return(0);}
   pMap_MUTEX = true;
   setTimeout("pMap_MUTEX=false",pMap_MUTEX_Timeout);

   /* Determine mouse position over the chart */
   if (pMap_IE)
    { pMap_MouseX = event.clientX + document.body.scrollLeft; pMap_MouseY = event.clientY + document.body.scrollTop; }
   else
    { pMap_MouseX = e.pageX; pMap_MouseY = e.pageY; }
   pMap_MouseX -= document.getElementById(pMap_ImageID).offsetLeft;
   pMap_MouseY -= document.getElementById(pMap_ImageID).offsetTop;

   /* Check if we are flying over a map zone
    * Lets use the following method to check if a given
    * point is in any convex polygon.
    * http://www.programmingforums.org/post168124-3.html
    */
   Found = false;
   for (Key in pMap_ImageMap)
    {
     Values = Key.split("--");
     SeriesName = Values[0];
     SeriesValue = Values[1];
     SignSum = 0;
     for (i = 0; i <= pMap_ImageMap[Key].length - 1; i++)
      {
      if (i == pMap_ImageMap[Key].length - 1)
       {
         index1 = i;
         index2 = 0;
       }
      else
       {
         index1 = i;
         index2 = i+1;
       }
      result = getDeterminant(
            pMap_ImageMap[Key][index1][0],
            pMap_ImageMap[Key][index1][1],
            pMap_ImageMap[Key][index2][0],
            pMap_ImageMap[Key][index2][1],
            pMap_MouseX,
            pMap_MouseY
       );
       if (result > 0) { SignSum += 1; } else { SignSum += -1; }
      }
     //console.log(Key+": "+SignSum);
     if (Math.abs(SignSum) == pMap_ImageMap[Key].length)
      {
       Found = true;
       if ( pMap_CurrentKey != Key )
        { overlib(SeriesValue, CAPTION, SeriesName, WIDTH, 80); pMap_CurrentKey = Key; }
      }
    }
   if ( !Found && pMap_CurrentKey != -1 ) { nd(); pMap_CurrentKey = -1; }
  }

 function getDeterminant(X1, Y1, X2, Y2, X3, Y3)
  {
   return (X2*Y3 - X3*Y2) - (X1*Y3 - X3*Y1) + (X1*Y2 - X2*Y1);
  }
  
 function LoadImageMap(ID, map)
  {
   pMap_ImageID = ID;
   pMap_ImageMap = JSON.parse(map);   
  }

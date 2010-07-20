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
 var pMap_CurrentMap    = -1;
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

   /* Check if we are flying over a map zone */
   Found = false;
   for (Map in pMap_ImageMap)
    {
     Values = pMap_ImageMap[Map].split(",");
     if ( pMap_MouseX>=Values[0] && pMap_MouseX<=Values[2])
      {
       if ( pMap_MouseY>=Values[1] && pMap_MouseY<=Values[3] )
        {
         Found = true;
         if ( pMap_CurrentMap != Map )
          { overlib(Values[5], CAPTION, Values[4], WIDTH, 80); pMap_CurrentMap = Map; }
        }
      }
     if ( !Found && pMap_CurrentMap != -1 ) { nd(); pMap_CurrentMap = -1; }
    }
  }

 function LoadImageMap(ID, map)  { pMap_ImageID = ID, pMap_ImageMap = map.split("-"); }

/* 
* This script manages the login form of PMA making it more interactive
* user can't submit the form until he/she puts in some value in the user and pass fields
* This can save network traffic
 */

$(function() {
    var user;
    var pass;
 

    $('#input_go').click(function(e){

      user = $("#input_username").val();  
      pass = $("#input_password").val();
      
      if(user === "" || pass === "")
      {
          if(user === "")
          $("#input_username").css("background-color", "pink");
      
           if(pass === "")
          $("#input_password").css("background-color", "pink");
      
          e.preventDefault();
         
      }
    
    });
    
    $("#input_username").focus(function(){
        
      $("#input_username").keydown(function(){
          
          $("#input_username").css("background-color", "#ACFB9A");
      });  
    });
    
    $("#input_password").focus(function(){
        
      $("#input_password").keydown(function(){
          
          $("#input_password").css("background-color", "#ACFB9A");
      });  
    });
    
    $("#input_username").focusout(function(){
        user = $("#input_username").val();
        
        if(user === "")
        $("#input_username").css("background-color", "pink");
        
    });
    
    $("#input_password").focusout(function(){
        pass = $("#input_password").val();
        
        if(pass === "")
        $("#input_password").css("background-color", "pink");
        
    });
});



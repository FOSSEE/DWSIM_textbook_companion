$( document ).ready(function() {
//to search 
 $('#searchtext').keyup(function(event) {
        var search_text = $('#searchtext').val();
        var rg = new RegExp(search_text,'i');
        $('#aicte-list-wrapper  .title , .form-item' ).each(function(){
             if($.trim($(this).html()).search(rg) == -1) {
             //alert("one");
                $(this).parent('div').css('background-color', '#ffffff');
                $(this).css('display', 'none');
                $(this).next().css('display', 'none');
                $(this).next().next().css('display', 'none');
            }  
            else {
            //alert("two");
                $(this).parent('div').css('background-color', '#ffffff');
                $(this).css('display', '');
                $(this).next().css('display', '');
                $(this).next().next().css('display', '');
            }
        });
    });
   
   //to clear the searched text
    $('#search_clear').click(function() {
    $('#searchtext').val('');   
   $('#aicte-list-wrapper  .title , .form-item' ).each(function(){
        $(this).parent().css('display', '');
        $(this).css('display', '');
        $(this).next().css('display', '');
        $(this).next().next().css('display', '');
    });
});
    

$('#edit-same-address').click(function() {
    var temp = $('#edit-chq-address').val();
    $('#edit-temp-chq-address').val(temp);
    var temp1 = $('#edit-perm-city').val();
    $('#edit-temp-city').val(temp1);
    var temp1 = $('#edit-perm-pincode').val();
    $('#edit-temp-pincode').val(temp1);
    var temp1 = $('#edit-perm-state').val();
    $('#edit-temp-state').val(temp1);

	$("#edit-cheque-sent").datepicker();


	$("#edit-cheque-cleared").datepicker();

});

$("#edit-perm-pincode").blur(function() 
    { 
        var string_length,string_val; 
        string_val = $("#edit-perm-pincode").val();
        string_length = $("#edit-perm-pincode").text().length; 
        //$("#username_warning").empty(); 
 
        if ((isNaN(string_val))&&(string_length < 6))
        alert("Not A Valid Zip Code!!");
        
           // $("#username_warning").append("Username is too short"); 
    }); 
$("#edit-temp-pincode").blur(function() 
    { 
        var string_length1,string_val1; 
        string_val = $("#edit-temp-pincode").val();
        string_length = $("#edit-temp-pincode").text().length; 
        //$("#username_warning").empty(); 
 
        if ((isNaN(string_val))&&(string_length1 < 6))
        alert("Not A Valid Zip Code!!");
        
           // $("#username_warning").append("Username is too short"); 
    }); 

$("#edit-mobileno1").blur(function() 
    { 
        var string_length3,string_val3; 
        string_val3 = $("#edit-mobileno1").val();
        string_length3 = $("#edit-mobileno1").text().length; 
        //$("#username_warning").empty(); 
 
        if (isNaN(string_val3))
	{
		alert("Mobile No should be a number!!");
           // $("#username_warning").append("Username is too short"); 
	}
        if((string_length3 > 0)&&(string_length3 < 11))
	{
        		alert("Not A Valid Mobile No!!");
        }
    }); 

$("#edit-mobileno2").blur(function() 
    { 
        var string_length4,string_val4; 
        string_val4 = $("#edit-mobileno2").val();
        string_length4 = $("#edit-mobileno2").text().length; 
        //$("#username_warning").empty(); 
 
        if (isNaN(string_val4))
	{
		alert("Mobile No should be a number!!");
	        // $("#username_warning").append("Username is too short"); 
	}
        if((string_length4 > 0)&&(string_length4 < 11))
	{
        		alert("Not A Valid Mobile No!!");
        }
    });
$('#edit-older-wrapper').hide();
$('#edit-version').change(function() {
    var selected = $(this).val();
    //$('#edit-older-wrapper').hide();
    if(selected == 'olderversion'){
         $('#edit-older-wrapper').show();
    }
    else
    {
	$('#edit-older-wrapper').hide();
    }    
});

/* hide nonaicte_proposal form textbox of other reason */
$('#edit-other-reason-wrapper').hide();
$(function() {
  enable_cb();
  $("#edit-reason-Other-reason").click(enable_cb);
});
function enable_cb() {
  if (this.checked) {
     $('#edit-other-reason-wrapper').show();
  } else {
    $('#edit-other-reason-wrapper').hide();
  }
}




/* highlighting current filter [A-Z] of book search pages */
var pathname = window.location.pathname;
var filter = pathname.charAt(pathname.length-1);
$filters = $("#filter-links a");
$filters.each(function() {
    
    var current = $(this).attr("href");
    var current = current.charAt(current.length-1);
    if(current == filter) {
        $(this).css({
            "padding": "0 2px",
            "color": "#000000",
            "font-weight": "bolder",
            "background-color": "#f5f5f5"
        });
    }
});

$report_form = $("#textbook-companion-aicte-report-form");
$("#aicte-report").click(function() {
    $("#textbook-companion-aicte-report-form").lightbox_me( {
            centered: true
    });
});

/* validate report_form and submit */
function danger(obj) {
    obj.css("border", "2px solid red");
}
function safe(obj) {
    obj.css("border", "2px solid #cccccc");
}
function validateEmail(email) { 
    var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
} 
$report_form.submit(function(e) {
    var $name = $("#edit-name");
    var $email = $("#edit-email");
    var $number = $("#edit-number");
    var $book = $("#edit-book");

    var errors = 0;
    /* reset errors */
    safe($name); safe($email); safe($number); safe($book);
    if(!$name.val()) {
        danger($name);
        errors = 1;
    }
    if(!validateEmail($email.val())) {
        danger($email);
        errors = 1;
    }
    if(!$number.val()) {
        danger($number);
        errors = 1;
    }
    if($book.val() == "0") {
        danger($book);
        errors = 1;
    }
    if(!errors) {
        $(this).submit();
    }
    e.preventDefault();
});

});

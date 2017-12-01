function checkEmail(email, page) {
    var response = false;
    if (validateEmail(email)) {

        $.ajax({
            type: 'POST',
            async: false,
            url: checkEmailSource,
            data: "email=" + email,
            success: function(data) {
                if (data == 'error') {
                    $("#ajaxEmailMsg").removeClass("success1").html("Email " + email + " already exists.").addClass("error1").show();
                    response = false;

                } else {
                    $("#ajaxEmailMsg").removeClass("error1").html("Email " + email + " is available.").addClass("success1").show();
                    response = true;

                }
            },
            error: function(data){
                response = true;
            }
        });
    } else {
        if (page != 'account') {
    		$("#ajaxEmailMsg").removeClass("success1").html("Please enter valid email.").addClass("error1").show();
        }
        response = false;
    }

    return response;
}


  function checkUsername(username) {
    var response = false;
    var ajaxUrl = checkEmailSource;
  
    if (username != "" && validateUsername(username)) {

        $.ajax({
            type: 'POST',
            async: false,
            url: ajaxUrl,
            data: "username=" + username,
            success: function (data) {

                if (data == 'error') {

                    $("#ajaxUsernameMsg").removeClass("success1").html("This username is not available.").addClass("error1").show();
                } else if (data == 'aradialExist') {
                    $("#username").val(username);
                    $(".welcomelabel .welcomemsg").html("<p>Welcome " + username + ",</p><p>We recently migrated to a new customer portal " +
                            "and will need a little more information from you to move your existing username and plan to our new portal.</p>" +
                            "<p>Please fill in the remaining fields – then browse our IPTV options, which you can bundle with your internet plan.</p>");
                    $("#forgotPasswordLink").removeClass('hide');
                    $('#credentialInput').removeClass('hide');
                    $('#flashnew').removeClass('hide');
                    $('.flashOverlay').removeClass('hide');
                    $("#authAradial").text("Submit");
                    $('#authAradial').removeClass('hide');
                    $('#authCountinuePopup').addClass('hide');

                    response = true;
                }else if (data == 'error1') {

                    $("#ajaxUsernameMsg").removeClass("success1").html("This aradial is not available.").addClass("error1").show();
					response = false;
                } else {
                    $("#ajaxUsernameMsg").removeClass("error1").html("This username " + username + " is available.").addClass("success1").show();
                    response = true;
                }
            },
            error: function(data){
                response = true;
            }
        });

    } else {
        $("#ajaxUsernameMsg").html("");
    }
    return response;
}

function checkAdminEmail(email, page) {
    var response = false;
    if (validateEmail(email)) {

        $.ajax({
            type: 'POST',
            async: false,
            url: checkEmailSource,
            data: "email=" + email,
            beforeSend: function () {
                $("#loader").show();
            },
            success: function (data) {

                if (data == 'error') {

                    if (page == 'account') {

                        $("#dhi_admin_registration_email").val(currentEmail);
                    }

                    $("#ajaxEmailMsg").removeClass("success").html("Email " + email + " already exists.").addClass("error").show();
                    $("#loader").hide();
                } else {

                    $("#ajaxEmailMsg").removeClass("error").html("Email " + email + " is available.").addClass("success").show();
                    $("#loader").hide();

                    response = true;
                }
            }
        });
    } else {

        $("#ajaxEmailMsg").html("");
    }
    return response;
}

function validateEmail(email) {

   	// var emailReg = new RegExp(/^(("[\w-\s]+")|([\w-]+(?:\.[\w-]+#)*)|("[\w-\s]+#")([\w-]+(?:\.[\w-]+#)*))(@((?:[\w-]+#\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][0-9]\.|1[0-9]{2}\.|[0-9]{1,2}\.))((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){2}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\]?$)/i);
    var emailReg = new RegExp(/^([\w-\.]+)([\w-]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/);
	var valid = emailReg.test(email);

    if (!valid) {
        return false;
    } else {
        return true;
    }
}

function validateUsername(username) {

    var usernameregex = new RegExp(/^[A-Za-z0-9-_!@./$]+$/);
    var valid = usernameregex.test(username);

    if (!valid) {
        return false;
    } else {
        return true;
    }
}

function disErrorMsg(msgType,msg){

	var html = '';
	html +='<div class="alert alert-'+msgType+'">';
	html +='<button type="button" class="close" data-dismiss="alert">&times;</button>';
	html += msg
	html +='</div>';
	return html;
}

function disAradialPasswordPopup(msgType,msg,username){

	var html = '';
	html +='<div class="alert alert-'+msgType+'">';
        html += msg;

        html +='<input type="text" name="pass" ><br>';
        html +='<input type="button" name="submit" value="submit"   >';
	html +='</div>';
	return html;
}

function checkAradialAuth(checkAuthSource, username, password) {
    var response = " ";
    $.ajax({
        type: 'POST',
        async: false,
        url: checkAuthSource,
        data: "username=" + username + "&password=" + password,
        beforeSend: function () {
            $("#loader").show();
        },
        success: function (data) {
            if (data.result == 'success') {
                var str = '';
                str += "<p>Please complete your account setup by clicking the continue button and filling out the registration form. </p>";
                str += "<p>On completion of the registration form your plan information will be migrated to the new portal</p>";
                str += "<p>You may need to choose a new password that complies with the new portal's password policy </p>";
                $('.emailSentMsg').html('');
                $("#authAradial").addClass("hide");
                $("#authCountinuePopup").removeClass("hide");
                $("#popupErrorMessage").addClass("hide");
                $('#credentialInput').addClass('hide');
                $(".welcomelabel .welcomemsg").html(str);
                $("#forgotPasswordLink").addClass('hide');
                $("#loader").hide();
                response = true;
            } else if (data.result == 'error') {

                $("#popupErrorMessage").html("Wrong Credentials");
                $("#loader").hide();
                response = false;
            }
            return response;
        },
        error: function (data) {
        }
    });
    return response;
}

function sendEmailAuthAradial(checkAuthSource, emailAradial, supportSource) {
    var response = '';
    $.ajax({
        type: 'POST',
        async: false,
        url: checkAuthSource,
        data: "emailAradial=" + emailAradial,
        beforeSend: function () {
            $("#loader").show();
        },
        success: function (data) {
            var response = '';
            if (data.result == 'success') {
                var str = "";
                $('.emailSentMsg').html("<p>  Your password has been sent to the email address you provided.</p>");
                $("#credentialInput").removeClass('hide');
                $("#popupErrorMessage").html('');
                $('#password').val('');
                $('#flashnew').removeClass('hide');
                $('#flashForgotPassword').addClass('hide');
                $('#forgotPasswordLink').removeClass('hide');
                response = true;
            } else if (data.result == 'error') {
                var str = "<p> We could not find the email address you provided in our database. Please contact customer service by filling out the ";
                str += '<a href="' + supportSource + '" target="_blank"> support form</a>.</p>';
                $('#authAradial').addClass('hide');
                $(".welcomelabel .welcomemsg").html(str);
                $("#popupErrorMessage").html('');
                $("#credentialInput").addClass('hide');
                $("#authAradial").text("Continue");
                $("#authAradial").text("Continue");
                $('#flashnew').removeClass('hide');
                $('#flashForgotPassword').addClass('hide');
                response = false;
            }
            return response;
        },
        error: function (data) {
        }
    });
    return response;
}
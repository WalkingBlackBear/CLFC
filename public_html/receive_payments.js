function show_receive_payment_form(member_id,basket_id) {
  // Get the receive_payment_form
  // var element_id = p_arrElements[j].attributes["id"].value;
  $.post("ajax/adjust_process.php", {
    process:"get_receive_payment_form",
    basket_id:basket_id,
    member_id:member_id
    },
  function(receive_payment_form) {
    // First kill off (delete) any existing receive_payment form on the page
    close_receive_payment_form();
    // Add the new receive payment form
    if (basket_id != 0)
      $(receive_payment_form).appendTo('#basket_id'+basket_id);
    else
      $(receive_payment_form).appendTo('#member_id'+member_id);
    });
  }

// Post the receive_payment information
function receive_payment(member_id,basket_id) {
  $.post("ajax/adjust_process.php", {
    // member_id:
    // posted_by:
    // delcode_id:
    // delivery_id:
    process:"receive_payment",
    member_id:member_id,
    basket_id:basket_id,
    amount:$("#amount").val(),
    effective_datetime:$("#effective_datetime").val(),
    payment_type:$("input[type='radio'][name='payment_type']:checked").val(),
    paypal_fee:$("#paypal_fee").val(),
    paypal_comment:$("#paypal_comment").val(),
    memo:$("#memo").val(),
    batch_number:$("#batch_number").val(),
    comment:$("#comment").val()
    },
  function(receive_payment) {
    // Returned value has first ten fixed characters indicating status
    var receive_payment_status = receive_payment.substr(0,10)
    var receive_payment_result = receive_payment.substr(10)
    if (receive_payment_status == "ACCEPT    ") {
      // Payment was recorded, so close the receive_payment form
      close_receive_payment_form();
      // Then reload the member information section
      reload_report_line(basket_id);
      }
    else if (receive_payment_status == "ERROR     ") {
      // Payment failed for some reason so clear the form and show it form again
      close_receive_payment_form();
      // Add the new receive payment form
      $(receive_payment_result).appendTo('#basket_id'+basket_id);
      }
    else {
      }
    });
  }


function close_receive_payment_form() {
  if ($('#receive_payment_row').length) {
    $('#receive_payment_row').replaceWith("");
    }
  }




function reload_report_line (basket_id) {
  $.post("ajax/adjust_report.php", {
    request:"basket_total_and_payments",
    basket_id:basket_id
    },
  function(adjust_report_data) {
    $("#basket_id"+basket_id).html(adjust_report_data);
    });
  }

// 
//       var adjust_report_line = "";
//       var adjust_report_line = adjust_report (basket_id);
//       // Set the new innerHTML for the member section...
//       document.getElementById("basket_id"+basket_id).innerHTML = adjust_report_line;
//       // $("#basket_id"+basket_id).html(adjust_report_line.substr(0));
// // need stuff here


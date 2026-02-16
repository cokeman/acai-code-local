
// Create an instance of Elements
var elements = stripe.elements();

// Custom styling can be passed to options when creating an Element.
// (Note that this demo uses a wider set of styles than the guide below.)
var style = {
    base: {
        color: '#32325d',
        lineHeight: '18px',
        fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
        fontSmoothing: 'antialiased',
        fontSize: '16px',
        '::placeholder': {
            color: '#aab7c4'
        }
    },
    invalid: {
        color: '#fa755a',
        iconColor: '#fa755a'
    }
};

// Create an instance of the card Element
var card = elements.create('card', {style: style});

// Add an instance of the card Element into the `card-element` <div>
card.mount('#card-element');

// Handle real-time validation errors from the card Element.
card.addEventListener('change', function(event) {
    var displayError = document.getElementById('card-errors');
    if (event.error) {
        displayError.textContent = event.error.message;
    } else {
        displayError.textContent = '';
    }
});

// Handle form submission
var form = document.getElementById('payment-form');

botonCompra = document.getElementById("botonCompra");
botonCompra.addEventListener("click",function(){ form.dispatchEvent(new Event("submit")); })

form.addEventListener('submit', function(event) {
    event.preventDefault();
    var error = 0;
    var checks = document.querySelectorAll("#payment-form .checks input[type='checkbox']");
    for (check of checks){
        if (check.required && !check.checked) error+=1;
    }
    if (error) { 
        var displayError = document.getElementById('card-errors-2');
        displayError.style.display = "block";
        return;
    }else{
        var displayError = document.getElementById('card-errors-2');
        displayError.style.display = "none";
    }
    
    document.getElementById("camposStripe").style.display="none";
    document.getElementById("loadingStripe").style.display="block";
    

    stripe.createToken(card).then(function(result) {
        if (result.error) {
            // Inform the user if there was an error
            var errorElement = document.getElementById('card-errors');
            errorElement.textContent = result.error.message;
            document.getElementById("camposStripe").style.display="block";
            document.getElementById("loadingStripe").style.display="none";
            
        } else {
            // Send the token to your server
            stripeTokenHandler(result.token);
        }
    });
});

if (localStorage.getItem("stripeEmail")) document.getElementById('stripeEmail').value = localStorage.getItem("stripeEmail");
function stripeTokenHandler(token) {
    
    if (!document.getElementById('stripeEmail').value) {alert("Error"); return;}
    
    localStorage.setItem("stripeEmail",document.getElementById('stripeEmail').value);
    
    // Insert the token ID into the form so it gets submitted to the server
    var form = document.getElementById('payment-form');
    var hiddenInput = document.createElement('input');
    hiddenInput.setAttribute('type', 'hidden');
    hiddenInput.setAttribute('name', 'stripeToken');
    hiddenInput.setAttribute('value', token.id);
    form.appendChild(hiddenInput);

    var hiddenInput2 = document.createElement('input');
    hiddenInput2.setAttribute('type', 'hidden');
    hiddenInput2.setAttribute('name', 'stripeEmail');
    hiddenInput2.setAttribute('value', document.getElementById('stripeEmail').value);
    form.appendChild(hiddenInput2);
	
	var data = $(form).serialize();
	var action = form.getAttribute('action');
	options = {options: options};
	if (action.includes('?')) {
		action += '&' + data + '&' + $.param(options);
	}
	else {
		action += '?' + data + '&' + $.param(options);
	}
	
	fetch(action)
	.then(data => data.text())
	.then(text => {
		$(form).replaceWith(text);
	});
	
    // Submit the form
    //form.submit();
}
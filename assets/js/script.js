/**
 * Function wich return the value of GET
 * @param param
 * @returns {null}
 */
function $_GET(param) {
	var vars = {};
	window.location.href.replace( location.hash, '' ).replace(
		/[?&]+([^=&]+)=?([^&]*)?/gi, // regexp
		function( m, key, value ) { // callback
			vars[key] = value !== undefined ? value : '';
		}
	);
	if ( param ) {
		return vars[param] ? vars[param] : null;	
	}
	return vars;
}

$(function(){
	$('.my_delete').click(function(e){
		return confirm("Suppression irreversilbe ! Voulez-vous continuer ?");
	});	

	// if the parameter demande is not null, then the admin is creating a new vendor profile
	if ($_GET('create') !== null)
	{
		$('input').attr('readonly','readonly');
		$('textarea').attr('readonly','readonly');		
		// ajax request to get the corresponding demande		
		$.ajax({
			type: 'GET',			
			url: 'http://172.18.0.8:80/demande/demandes/'+$_GET('create'),			
			timeout: 10000,
			beforeSend: function(xhr){
				xhr.setRequestHeader("Authorization", "Bearer "+ $.cookie('token'));
				$(':submit').attr('disabled','disabled');
				$('label:not(label:last)').append('&nbsp;<img style="weight:15px;height:15px" class="load" src="http://www.mediaforma.com/sdz/jquery/ajax-loader.gif">');
			},
			//async: false,
			success: function(data){
				console.log(data);
				$('.load').remove();
				$('#yith_vendor_paypal_email').val(data.email);
				$('#yith_vendor_enable_selling').attr('checked','true');
				$('#edittag > table > tbody > tr.form-field.yith-choosen > td').prepend('<i style="background-color:red">( Choisir utilisateur : '+data.user_id+' ) </i>');
				$(':submit').removeAttr('disabled'); // enable the submit button to allow creating new vendors				
			},
			error: function(){
				$('.load').remove();
				alert('Erreur dans le chargement des donnees. \nVeuillez recharger la page !');
				$(':submit').attr('disabled','disabled'); // submit button will be disable if the requeset failed, so user can't create a wrong vendors
			}
		});
		/*
		Problem : if the admin enter other user id ???
		$('body > span > span > span.select2-search.select2-search--dropdown > input').keyUp(function(){
			console.log($(this).val());
			//if($(this).val())
		});*/
	}

	/* when a filleul is going to register to woocommerce, email input'll be readonly to make 
	 * sure that the filleul's email will be the email to which the parrain
	 * sent the invitation
	 */ 
	if ($_GET('parrainage') == '1') {
		$('#reg_email').attr('readonly', 'readonly');
	}

	$('#emails').one('focus',function(){
		$(this).val("");
	});	
});
<form method="post" action="">
	<label for="emails">Email de(s) personne(s) &agrave; parrain&eacute;e(s) :</label><br/>
	<textarea name="emails" id="emails" rows="5" cols="50" required>
		Entrez les emails ici s&eacute;par&eacute;s par un point-virgule <br/>
		(Email1@email.com;Email@email.com)
	</textarea>
	<input type="hidden" name="send_invitation" value="1">
	<?php submit_button('Envoyer l\'invitation')?>	
</form>
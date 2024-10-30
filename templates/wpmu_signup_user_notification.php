<table width="100%" style="padding: 10px;">
	<tr>
		<td>
			
			<?php if( $welcome_msg ) echo $welcome_msg; ?>
			
			<p><?php _e( 'To activate your account, please click on the link below. After you activate, you will receive *another email* with your login.', 'html-emails' ); ?></p>
			
			<table cellspacing="5" cellpadding="3">
				<tr>
					<?php htmlize_the_action_button( $activation_url, __( 'Activate', 'html-emails' ), '#006505' ); ?>
					<?php if( is_array( $links ) ) : ?>
						<?php foreach( $links as $link_url => $link_text ) : ?>
							<?php htmlize_the_action_button( $link_url, $link_text ); ?>
						<?php endforeach ?>
					<?php endif; ?>
				</tr>
			</table>
		</td>
	</tr>
</table>


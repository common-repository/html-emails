<table width="100%" style="padding: 10px;">
	<tr>
		<td>
			<?php if( $welcome_msg ) echo $welcome_msg; ?>
			
			<?php if( $show_login ) : ?>
				<table width="100%" style="padding: 10px;">
					<tr>
						<td valign="top">
							<div style="color: #222; margin-top: 4px;">
								<strong><?php _e('Username: ', 'html-emails'); ?></strong>
								<?php echo $user->data->user_login; ?>
							</div>
							<div style="color: #222; margin-top: 4px;">
								<strong><?php _e('Password: ', 'html-emails'); ?></strong>
								<?php echo $user_password; ?>
							</div>
						</td>
					</tr>
				</table>
			<?php endif; ?>
			
			<table cellspacing="5" cellpadding="3">
				<tr>
					<?php htmlize_the_action_button( wp_login_url(), __('Login', 'html-emails'), '#006505' ); ?>
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
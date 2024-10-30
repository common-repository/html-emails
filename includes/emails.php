<?php
if ( ! function_exists('wp_notify_postauthor') ) :
	function wp_notify_postauthor ( $comment_id, $comment_type='' ) {
		$comment = get_comment( $comment_id );
		$post    = get_post( $comment->comment_post_ID );
		$user    = get_userdata( $post->post_author );
		$current_user = wp_get_current_user();
		
		if ( $comment->user_id == $post->post_author ) return false; // The author moderated a comment on his own post
		
		if ( '' == $user->user_email ) return false; // If there's no email to send the comment to
		
		$comment_author_domain = @gethostbyaddr($comment->comment_author_IP);
		
		$blogname = htmlize_get_blogname();
		
		if ( empty( $comment_type ) ) $comment_type = 'comment';
		$comment_type_text = htmlize_get_comment_type_text( $comment_type );
		
		$email_data = array();
		$email_data['email_title']	 = sprintf( __('New %s on your post', 'html-emails'), $comment_type_text );
		/* translators: 1: post title, 2: post id */
		$subtitle = sprintf( __( '<em>%1$s</em> #%2$s', 'html-emails' ), $post->post_title, $comment->comment_post_ID );
		$email_data['email_subtitle']  = htmlize_maybe_linkify( get_permalink( $comment->comment_post_ID ), $subtitle );
		$email_data['email_templates'] = array( "notify_postauthor_$comment_type.php", 'notify_postauthor.php', 'notify_comment.php' );
		
		$email_data['email_data'] = array(
				'comment' 			=> $comment,
				'comment_type' 		=> $comment_type,
				'comment_type_text' => $comment_type_text,
				'comment_moderate' 	=> false,
				'post'			=> $post,
				'user'			=> $user,
				'current_user' 	=> $current_user
			);
		
		$notify_message = htmlize_message( $email_data );
		/* translators: 1: blog name, 2: comment type, 3: post title */
		$subject = sprintf( __( '[%1$s] %2$s: "%3$s"', 'html-emails' ), $blogname, $comment_type_text, $post->post_title );
		$wp_email = htmlize_get_wp_email();
		
		if ( '' == $comment->comment_author ) {
			$from = "From: \"$blogname\" <$wp_email>";
			if ( '' != $comment->comment_author_email )
				$reply_to = "Reply-To: $comment->comment_author_email";
		} else {
			$from = "From: \"$comment->comment_author\" <$wp_email>";
			if ( '' != $comment->comment_author_email )
				$reply_to = "Reply-To: \"$comment->comment_author_email\" <$comment->comment_author_email>";
		}
		
		$message_headers = "$from\n";
		$message_headers .= htmlize_get_message_headers();
		
		if ( isset($reply_to) )
			$message_headers .= $reply_to . "\n";
		
		$to_email = $user->user_email;
		$notify_message = apply_filters('comment_notification_text', $notify_message, $comment_id);
		$subject = apply_filters('comment_notification_subject', $subject, $comment_id);
		$message_headers = apply_filters('comment_notification_headers', $message_headers, $comment_id);
		
		wp_mail($to_email, $subject, $notify_message, $message_headers);
		
		return true;
	}
endif;

if ( !function_exists('wp_notify_moderator') ) :
	function wp_notify_moderator( $comment_id ) {
		global $wpdb;
		
		if( get_option( "moderation_notify" ) == 0 )
			return true;
		
		$comment = get_comment( $comment_id );
		$post = get_post( $comment->comment_post_ID );
		
		$comment_author_domain = @gethostbyaddr($comment->comment_author_IP);
		$comments_waiting = $wpdb->get_var("SELECT count(comment_ID) FROM $wpdb->comments WHERE comment_approved = '0'");
		
		$comment_type = $comment->comment_type ? $comment->comment_type : 'comment';
		$comment_type_text = htmlize_get_comment_type_text( $comment_type );
		
		$blogname = htmlize_get_blogname();
		
		$email_data = array();
		
		$email_data['email_title'] = sprintf( __( 'New %s awaiting approval', 'html-emails' ), $comment_type_text );
		/* translators: 1: post title, 2: post id */
		$subtitle = sprintf( __( '<em>%1$s</em> #%2$s', 'html-emails'), $post->post_title, $comment->comment_post_ID );
		$email_data['email_subtitle'] = htmlize_maybe_linkify( get_permalink( $comment->comment_post_ID ), $subtitle );
		$email_data['email_templates'] = array( "notify_moderator_$comment_type.php", 'notify_moderator.php', 'notify_comment.php' );
		
		$email_data['email_data'] = array(
			'comment' 			=> $comment,
			'comment_type' 		=> $comment_type,
			'comment_type_text' => $comment_type_text,
			'comment_moderate' 	=> true,
			'comments_waiting'  => $comments_waiting,
			'post'				=> $post
		);
		
		$notify_message = htmlize_message( $email_data );
		
		/* translators: 1: blog name, 2: post title */
		$subject = sprintf( __( '[%1$s] Please moderate: "%2$s"', 'html-emails' ), $blogname, $post->post_title );
		$to_email = get_option('admin_email');
		
		$message_headers = htmlize_get_message_headers();
		
		$notify_message = apply_filters('comment_moderation_text', $notify_message, $comment_id);
		$subject = apply_filters('comment_moderation_subject', $subject, $comment_id);
		$message_headers = apply_filters('comment_moderation_headers', $message_headers);
		
		@wp_mail($to_email, $subject, $notify_message, $message_headers);
		
		return true;
	}
endif;

if ( !function_exists('wp_password_change_notification') ) :
	function wp_password_change_notification(&$user) {
		$wp_user = new WP_User( $user->ID );
		
		$admin_email = get_option('admin_email');
		
		// send a copy of password change notification to the admin
		// but check to see if it's the admin whose password we're changing, and skip this
		if ( $wp_user->user_email != $admin_email ) {
			
			$blogname = htmlize_get_blogname();
			$subject = sprintf(__('[%s] Password Changed', 'html-emails'), $blogname);
			
			$email_data = array(
				'email_title' => __( 'Password Changed', 'html-emails' ),
				'email_subtitle' => '',
				'email_templates' => array( 'password_change_admin.php' ),
			);
			
			$email_data['email_data'] = array(
				'user' => $wp_user,
			);
			
			$message = htmlize_message( $email_data );
			$message_headers = htmlize_get_message_headers();
			
			wp_mail($admin_email, $subject, $message, $message_headers);
		}
	}
endif;

if ( !function_exists('wp_new_user_notification') ) :
	function wp_new_user_notification( $user_id, $plaintext_pass = '' ) {
		$user = new WP_User( $user_id );
		
		wp_new_user_admin_notification( $user );
		
		if ( empty($plaintext_pass) )
			return;
		
		wp_new_user_user_notification( $user, $plaintext_pass );
	}
	
	function wp_new_user_admin_notification( $user ) {
		$admin_email = get_option('admin_email');
		$user_login = stripslashes($user->user_login);
		$user_email = stripslashes($user->user_email);
		
		$blogname = htmlize_get_blogname();
		
		$subject = sprintf(__('[%s] New User Registration', 'html-emails'), $blogname);
		
		$email_data = array(
			'email_title' => __( 'New user registration', 'html-emails' )
			, 'email_subtitle' => ''
			, 'email_templates' => array( 'new_user_admin.php' )
		);
		
		$email_data['email_data'] = array(
			'user' => $user
			, 'blogname' => $blogname
		);
		
		$message = htmlize_message( $email_data );
		$message_headers = htmlize_get_message_headers();
		
		@wp_mail($admin_email, $subject, $message, $message_headers);
	}
	
	function wp_new_user_user_notification( $user, $password ) {
		
		$blogname = htmlize_get_blogname();
		
		$user_email = $user->user_email;
		$welcome_msg = sprintf( __('Hello %s. Your account has been created and is ready. Your login information is below! Happy WordPress-ing!', 'html-emails'), $user->display_name);
		$links = array();
		
		$email_data = array(
			'email_title' => sprintf( __( 'Welcome to %s', 'html-emails' ), $blogname ),
			'email_subtitle' => '',
			'email_templates' => array( 'new_user_user.php' )
		);
		
		$email_data['email_data'] = array(
			'user' => $user,
			'user_password' => $password,
			'welcome_msg' => apply_filters('wp_new_user_notification_welcome_message', $welcome_msg), // configurable welcome message
			'links' => apply_filters('wp_new_user_notification_links', $links),
			'admin_email' => apply_filters('wp_new_user_notification_admin_email', ''),
		);
		
		$subject = sprintf(__('[%s] Your username and password', 'html-emails'), $blogname);
		
		$message = htmlize_message( $email_data );
		$message_headers = htmlize_get_message_headers();
		
		wp_mail($user_email, $subject, $message, $message_headers);
	}
	
endif;

function htmlize_wpmu_signup_blog_notification( $domain, $path, $title, $user, $user_email, $key, $meta ) {
	global $current_site;

	// Send email with activation link.
	if ( !is_subdomain_install() || $current_site->id != 1 )
		$activation_url = network_site_url( "wp-activate.php?key=$key" );
	else
		$activation_url = "http://{$domain}{$path}wp-activate.php?key=$key"; // @todo use *_url() API
	
	$admin_email = htmlize_get_admin_email();
	$from_name = htmlize_get_from_name();

	$activation_url = esc_url( $activation_url );
	$site_url = esc_url( "http://{$domain}{$path}" );
	$welcome_msg = '';
	$links = array(
		$site_url => 'Visit Site',
		sprintf( 'mailto:%s', $admin_email ) => __( 'Contact Administrator', 'html-emails' )
	);
	
	$email_data = array(
		'email_title' => 'Activate Site',
		'email_subtitle' => htmlize_maybe_linkify( $site_url, esc_html( $title ) ),
		'email_templates' => array( 'wpmu_signup_blog_notification.php' )
	);
	
	$email_data['email_data'] = array(
		'site_url' => $site_url,
		'activation_url' => $activation_url,
		
		'title' => $title,
		
		'user' => $user,
		'user_email' => $user_email,
		'key' => $key,
		'meta' => $meta,
		
		'welcome_msg' => apply_filters('wpmu_signup_blog_notification_welcome_message', $welcome_msg), // configurable welcome message
		'links' => apply_filters('wpmu_signup_blog_notification_links', $links),
	);

	$message = htmlize_message( $email_data );
	echo $message;

	// TODO: Don't hard code activation link.
	$subject = sprintf( apply_filters( 'wpmu_signup_blog_notification_subject', __( '[%1s] Activate %3$s (%2s)' ) ), $from_name, $site_url, $title );

	$message_headers = htmlize_get_message_headers( htmlize_get_from_header( $from_name, $admin_email ) );

	
	//wp_mail($user_email, $subject, $message, $message_headers);
	
	// return false so that WordPress core doesn't also send an email
	return false;
}

function htmlize_wpmu_signup_user_notification( $user_login, $user_email, $key, $meta ) {
	// Send email with activation link.
	
	$admin_email = htmlize_get_admin_email();
	$from_name = htmlize_get_from_name();
	$activation_url = site_url( "wp-activate.php?key=$key" );
	$welcome_msg = apply_filters( 'wpmu_signup_user_notification_welcome_message', '' );
	
	$links = array(
		sprintf( 'mailto:%s', $admin_email ) => 'Contact Administrator'
	);
	
	$email_data = array(
		'email_title' => 'Activate Account',
		'email_subtitle' => '',
		'email_templates' => array( 'wpmu_signup_user_notification.php' )
	);
	
	$email_data['email_data'] = array(
		'user_login' => $user_login,
		'user_email' => $user_email,
		'activation_url' => $activation_url,
		'key' => $key,
		'meta' => $meta,
		'welcome_msg' => $welcome_msg, // configurable welcome message
		'links' => apply_filters('wpmu_signup_user_notification_links', $links),
	);
	
	$message = htmlize_message( $email_data );
	
	// TODO: Don't hard code activation link.
	$subject = sprintf( __( apply_filters( 'wpmu_signup_user_notification_subject', '[%1s] Activate %2s' ) ), $from_name, $user_login );
	echo $message;
	
	$message_headers = htmlize_get_message_headers( htmlize_get_from_header( $from_name, $admin_email ) );
	
	//wp_mail( $user_email, $subject, $message, $message_headers );

	// return false so that WordPress core doesn't also send an email
	return false;
}

function htmlize_wpmu_welcome_notification( $blog_id, $user_id, $password, $title, $meta ) {
	global $current_site;
	
	$admin_email = htmlize_get_admin_email();
	$from_name = htmlize_get_from_name();
	$show_login = true;
	
	if ( empty( $current_site->site_name ) )
		$current_site->site_name = 'WordPress MU';

	$url = get_blogaddress_by_id( $blog_id );
	$user = new WP_User( $user_id );

	$welcome_msg = stripslashes( get_site_option( 'welcome_email' ) );
	if ( $welcome_msg == false )
		$welcome_msg = stripslashes( __( 'Dear User,

Your new SITE_NAME site has been successfully set up at:
BLOG_URL

You can log in to the administrator account with the following information:
Username: USERNAME
Password: PASSWORD
Login Here: BLOG_URLwp-login.php

We hope you enjoy your new site.
Thanks!

--The Team @ SITE_NAME' ) );
	
	$welcome_msg = str_replace( 'SITE_NAME', $current_site->site_name, $welcome_msg );
	$welcome_msg = str_replace( 'BLOG_TITLE', $title, $welcome_msg );
	if( strpos( $welcome_msg, 'USERNAME' ) !== false && strpos( $welcome_msg, 'PASSWORD' ) !== false && strpos( $welcome_msg, 'BLOG_URL' ) !== false ) {	
		$welcome_msg = str_replace( 'BLOG_URL', $url, $welcome_msg );
		$welcome_msg = str_replace( 'USERNAME', $user->user_login, $welcome_msg );
		$welcome_msg = str_replace( 'PASSWORD', $password, $welcome_msg );
		$show_login = false;
	}

	$welcome_msg = wpautop( apply_filters( 'update_welcome_email', $welcome_msg, $blog_id, $user_id, $password, $title, $meta ) );
	
	$links = array(
		sprintf( 'mailto:%s', $admin_email ) => __( 'Contact Administrator', 'html-emails' )
		, htmlize_get_user_profile_link() => __( 'Edit Profile', 'html-emails' )
	);
	
	$email_data = array(
		'email_title' => 'Welcome',
		'email_subtitle' => '',
		'email_templates' => array( 'wpmu_welcome_notification.php' )
	);
	
	$email_data['email_data'] = array(
		'welcome_msg' => $welcome_msg // configurable welcome message
		, 'links' => apply_filters('wpmu_welcome_notification_links', $links)
		, 'show_login' => $show_login
	);

	$subject = apply_filters( 'update_welcome_subject', sprintf(__('New %1$s Site: %2$s'), $current_site->site_name, stripslashes( $title ) ) );
	$message_headers = htmlize_get_message_headers( htmlize_get_from_header( $from_name, $admin_email ) );
	
	$message = htmlize_message( $email_data );
	echo $message;
	
	//wp_mail($user->user_email, $subject, $message, $message_headers);
	
	// return false so that WordPress core doesn't also send an email
	return false;
}

function htmlize_wpmu_welcome_user_notification( $user_id, $password, $meta ) {
	global $current_site;
	
	$user = new WP_User($user_id);
	$admin_email = htmlize_get_admin_email();
	$from_name = htmlize_get_from_name();
	$show_login = true;
	
	if ( empty( $current_site->site_name ) )
		$current_site->site_name = 'WordPress MU';
	
	$welcome_msg = get_site_option( 'welcome_user_email' );
	
	$welcome_msg = apply_filters( 'update_welcome_user_email', $welcome_msg, $user_id, $password, $meta);
	$welcome_msg = str_replace( 'SITE_NAME', $current_site->site_name, $welcome_msg );
	if( strpos( $welcome_msg, 'USERNAME' ) !== false && strpos( $welcome_msg, 'PASSWORD' ) !== false && strpos( $welcome_msg, 'LOGINLINK' ) !== false ) {
		$welcome_msg = str_replace( 'USERNAME', $user->user_login, $welcome_msg );
		$welcome_msg = str_replace( 'PASSWORD', $password, $welcome_msg );
		$welcome_msg = str_replace( 'LOGINLINK', wp_login_url(), $welcome_msg );
		$show_login = false;
	}

	$welcome_msg = wpautop( $welcome_msg );
	
	$links = array(
		sprintf( 'mailto:%s', $admin_email ) => __( 'Contact Administrator', 'html-emails' )
	);
	
	$email_data = array(
		'email_title' => 'Welcome',
		'email_subtitle' => '',
		'email_templates' => array( 'wpmu_welcome_user_notification.php' )
	);
	
	$email_data['email_data'] = array(
		'welcome_msg' => $welcome_msg // configurable welcome message
		, 'links' => apply_filters('wpmu_welcome_user_notification_links', $links)
		, 'show_login' => $show_login
	);
	
	$subject = apply_filters( 'update_welcome_user_subject', sprintf(__('New %1$s User: %2$s'), $current_site->site_name, $user->user_login) );
	$message_headers = htmlize_get_message_headers( htmlize_get_from_header( $from_name, $admin_email ) );
	
	$message = htmlize_message( $email_data );
	echo $message;
	
	//wp_mail($user->user_email, $subject, $message, $message_headers);
	
	// return false so that WordPress core doesn't also send an email
	return false;
}
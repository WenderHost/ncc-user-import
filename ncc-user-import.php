<?php

if( ! isset( $args[0] ) || empty( $args[0] ) )
  WP_CLI::error('Please provide a filename as the first positional argument to this script.');
$filename = $args[0];

if( ! file_exists( $filename ) )
  WP_CLI::error('File `' . $filename . '` not found.');

$row = 1;
$errors = [];
$emails = [];
$existing_users = 0;
if( ( $handle = fopen( $filename, 'r' ) ) !== false ){
  while( ( $data = fgetcsv( $handle, 2048 ) ) !== false ){
    $num = count( $data );
    //WP_CLI::line( $num . ' fields in line ' . $row );
    $firstname = $data[0];
    $lastname = $data[1];
    $email = strtolower( $data[2] );
    if( ! is_email( $email ) ){
      $errors[] = [
        'type'    => 'invalidemail',
        'message' => 'Row ' . $row . ': Invalid email for ' . $firstname . ' ' . $lastname . ' (`' . $email . '`).',
      ];
      continue;
    }
    if( ! array_search( $email, $emails ) ){
      $users[] = [
        'firstname' => $firstname,
        'lastname'  => $lastname,
        'email'     => $email,
      ];
    } else {
      $errors[] = [
        'type'    => 'duplicateemail',
        'message' => 'Row ' . $row . ': Duplicate email (`' . $email . '`)',
      ];
    }
    $row++;
  }

  // Display the users in a table:
  //WP_CLI\Utils\format_items( 'table', $users, ['firstname','lastname','email'] );

  // Create the Users
  foreach( $users as $user ){
    if( email_exists( $user['email'] ) ){
      $existing_users++;
      continue;
    }

    $user_id = wp_insert_user([
      'user_pass' => wp_generate_password( 12 ),
      'user_login' => $user['email'],
      'user_email' => $user['email'],
      'display_name' => $user['firstname'],
      'first_name' => $user['firstname'],
      'last_name' => $user['lastname'],
      'role' => 'subscriber',
    ]);
    if( $user_id )
      WP_CLI::success( 'Created user: ' . $user['firstname'] . ' ' . $user['lastname'] . ' (' . $user['email'] . ').' );
  }

  WP_CLI::success('Opened `' . basename( $filename ) . '` with ' . $row . ' lines.');
  WP_CLI::line('👉 ' . $existing_users . ' users already existed.');
  if( 0 < count( $errors ) ){
    WP_CLI::line("\n" . str_repeat('-', 20 ) . ' ERRORS ' . str_repeat('-', 20 ) . "\n" . 'I found the following errors:');
    foreach( $errors as $error ){
      WP_CLI::line('🚨 ' . $error['message'] );
    }
  }
  fclose( $handle );
} else {
  WP_CLI::error('I could not open the file you supplied. Please check the file\'s permissions.');
}



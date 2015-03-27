<?php
// $Id$

function code_approval()
{
  /* get a list of unapproved chapters */
  $pending_chapter_q = db_query("SELECT c.id as c_id, c.number as c_number, c.name as c_name, c.preference_id as c_preference_id FROM {textbook_companion_example} as e JOIN {textbook_companion_chapter} as c ON c.id = e.chapter_id WHERE e.approval_status = 0");
  if (!$pending_chapter_q)
  {
    drupal_set_message(t('There are no pending code approvals.'), 'status');
    return '';
  }
  $rows = array();
  while ($row = db_fetch_object($pending_chapter_q))
  {
    /* get preference data */
    $preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $row->c_preference_id);
    $preference_data = db_fetch_object($preference_q);
    /* get proposal data */
    $proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $preference_data->proposal_id);
    $proposal_data = db_fetch_object($proposal_q);
    /* setting table row information */
    $rows[] = array($preference_data->book, $row->c_number, $row->c_name, $proposal_data->full_name, l('Edit', 'code_approval/approve/' . $row->c_id));
  }

  /* check if there are any pending proposals */                                   
  if (!$rows)
  {
    drupal_set_message(t('There are no pending proposals'), 'status');
    return '';
  }

  $header = array('Title of the Book', 'Chapter Number', 'Title of the Chapter', 'Contributor Name', 'Actions');
  $output = theme_table($header, $rows);
  return $output; 
}

function code_approval_form($form_state)
{
  /* get a list of unapproved chapters */
  $chapter_id = arg(2);
  $pending_chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $chapter_id);
  if ($pending_chapter_data = db_fetch_object($pending_chapter_q))
  {
    /* get preference data */
    $preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $pending_chapter_data->preference_id);
    $preference_data = db_fetch_object($preference_q);
    /* get proposal data */
    $proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $preference_data->proposal_id);
    $proposal_data = db_fetch_object($proposal_q);
  } else {
    drupal_set_message(t('Invalid chapter selected.'), 'error');
    drupal_goto('code_approval');
    return;
  }

  $form['#tree'] = TRUE;

  $form['contributor'] = array(
    '#type' => 'item',
    '#value' => $proposal_data->full_name,
    '#title' => t('Contributor Name'),
  );

  $form['book_details']['book'] = array(
    '#type' => 'item',
    '#value' => $preference_data->book,
    '#title' => t('Title of the Book'),
  );

  $form['book_details']['number'] = array(
    '#type' => 'item',
    '#value' => $pending_chapter_data->number,
    '#title' => t('Chapter Number'),
  );

  $form['book_details']['name'] = array(
    '#type' => 'item',
    '#value' => $pending_chapter_data->name,
    '#title' => t('Title of the Chapter'),
  );

  $form['book_details']['back_to_list'] = array(
    '#type' => 'item',
    '#value' => l('Back to Code Approval List', 'code_approval'),
  );

  /* get example data */
  $example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND approval_status = 0", $chapter_id);
  while ($example_data = db_fetch_object($example_q))
  {
    $form['example_details'][$example_data->id] = array(
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => TRUE,
    );
    $form['example_details'][$example_data->id]['example_number'] = array(
      '#type' => 'item',
      '#value' => $example_data->number,
      '#title' => t('Example Number'),
    );
  
    $form['example_details'][$example_data->id]['example_caption'] = array(
      '#type' => 'item',
      '#value' => $example_data->caption,
      '#title' => t('Example Caption'),
    );

    $form['example_details'][$example_data->id]['download'] = array(
      '#type' => 'markup',
      '#value' => l('Download Example', 'download/example/' . $example_data->id),
    );

    $form['example_details'][$example_data->id]['approved'] = array(
      '#type' => 'radios',
      '#options' => array('Approved', 'Dis-approved'),
    );

    $form['example_details'][$example_data->id]['message'] = array(
      '#type' => 'textfield',
      '#title' => t('Reason for dis-approval'),
    );

    $form['example_details'][$example_data->id]['example_id'] = array(
      '#type' => 'hidden',
      '#value' => $example_data->id,
    );
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit')
  );
  return $form;
}

function code_approval_form_submit($form, &$form_state)
{
  global $user;

  foreach ($form_state['values']['example_details'] as $ex_id => $ex_data)
  {
    $example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d LIMIT 1", $ex_data['example_id']);
    $example_data = db_fetch_object($example_q);
    $chapter_data = db_fetch_object(db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d LIMIT 1", $example_data->chapter_id));
    $preference_data = db_fetch_object(db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d LIMIT 1", $chapter_data->preference_id));
    $proposal_data = db_fetch_object(db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d LIMIT 1", $preference_data->proposal_id));
    $user_data = user_load($proposal_data->uid);

    del_book_pdf($preference_data->id);

    if ($ex_data['approved'] == "0")
    {
      db_query("UPDATE {textbook_companion_example} SET approval_status = 1, approver_uid = %d, approval_date = %d WHERE id = %d", $user->uid, time(), $ex_data['example_id']);

      /* sending email */
      $email_to = $user_data->mail;
      $param['example_approved']['example_id'] = $ex_data['example_id'];
      $param['example_approved']['user_id'] = $user_data->uid;
      if (!drupal_mail('textbook_companion', 'example_approved', $email_to, language_default(), $param, variable_get('textbook_companion_from_email', NULL), TRUE))
        drupal_set_message('Error sending email message.', 'error');

    } else if ($ex_data['approved'] == "1") {
      if (delete_example($ex_data['example_id']))
      {
        /* sending email */
        $email_to = $user_data->mail;
        $param['example_disapproved']['example_number'] = $example_data->number;
        $param['example_disapproved']['example_caption'] = $example_data->caption;
        $param['example_disapproved']['user_id'] = $user_data->uid;
        $param['example_disapproved']['message'] = $ex_data['message'];
        if (!drupal_mail('textbook_companion', 'example_disapproved', $email_to, language_default(), $param, variable_get('textbook_companion_from_email', NULL), TRUE))
          drupal_set_message('Error sending email message.', 'error');
      } else {
        drupal_set_message('Error disapproving and deleting example. Please contact administrator.', 'error');
      }
    }
  }
  drupal_set_message('Updated successfully.', 'status');
  drupal_goto('code_approval');
}


/******************************************************************************/
/********************************* BULK APPROVAL ******************************/
/******************************************************************************/

function bulk_approval_form($form_state)
{
  $form['#redirect'] = FALSE;

  //ahah_helper_register($form, $form_state);

  /* default value for ahah fields 
  if (!isset($form_state['storage']['run']['book']))
  {
    $book_default_value = 0;
  } else {
    $book_default_value =  $form_state['storage']['run']['book'];
  }

  if (!isset($form_state['storage']['run']['chapter']))
  {
    $chapter_default_value = 0;
  } else {
    if ($form_state['values']['run']['book_hidden'] !=  $form_state['values']['run']['book'])
      $chapter_default_value = 0;
    else
      $chapter_default_value = $form_state['storage']['run']['chapter'];
  }

  if (!isset($form_state['storage']['run']['example']))
  {
    $example_default_value = 0;
  } else {
    if ($form_state['values']['run']['book_hidden'] !=  $form_state['values']['run']['book'])
      $example_default_value = 0;
    else if ($form_state['values']['run']['chapter_hidden'] !=  $form_state['values']['run']['chapter'])
      $example_default_value = 0;
    else
      $example_default_value = $form_state['storage']['run']['example'];
  }*/

  $form['run'] = array(
    '#type' => 'fieldset',
    '#title' => t('Bulk Manage Code'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
    '#prefix' => '<div id="run-wrapper">',
    '#suffix' => '</div>',
    '#tree' => TRUE,
  );

  $form['run']['book'] = array(
      '#type' => 'select',
      '#title' => t('Title of the Book'),
      '#options' => _list_of_books(),
      '#default_value' => $book_default_value,
      '#tree' => TRUE,
      // '#ahah' => array(
      //   'event'   => 'change',
      //   'effect' => 'none',
      //   'path'    => ahah_helper_path(array('run')),
      //   'wrapper' => 'run-wrapper',
      //   'progress' => array(
      //     'type' => 'throbber',
      //     'message' => t(''),
      //   ),
      // ),
  );

  /* hidden form elements */
  $form['run']['book_hidden'] = array(
    '#type'  => 'hidden',
    '#value' =>  $form_state['values']['run']['book'],
  );

  /* hidden form elements */
  $form['run']['chapter_hidden'] = array(
    '#type'  => 'hidden',
    '#value' =>  $form_state['values']['run']['chapter'],
  );

 // if ($book_default_value > 0)
 // {
    $form['run']['download_book'] = array(
      '#type' => 'item',
      '#value' => l('Download', 'full_download/book/' . $book_default_value) . ' ' . t('(Download all the approved and unapproved examples of the entire book)'),
    );
    $form['run']['download_pdf'] = array(
      '#type' => 'item',
      '#value' => l('Download PDF', 'textbook_companion/generate_book/' . $book_default_value . '/1') . ' ' . t('(Download PDF of all the approved and unapproved examples of the entire book)'),
    );
    $form['run']['regenrate_book'] = array(
      '#type' => 'item',
      '#value' => l('Regenerate PDF', 'textbook_companion/delete_book/' . $book_default_value) . ' ' . t('(Manually Regenerate PDF of the entire book)'),
    );
    $form['run']['notes_book'] = array(
      '#type' => 'item',
      '#value' => l('Notes for Reviewers', 'code_approval/notes/' . $book_default_value),
    );

    $form['run']['approve_book'] = array(
      '#type'  => 'checkbox',
      '#title' => t('Approve Entire Book'),
    );
    $form['run']['unapprove_book'] = array(
      '#type'  => 'checkbox',
      '#title' => t('Pending Review Entire Book'),
    );
    $form['run']['disapprove_book'] = array(
      '#type'  => 'checkbox',
      '#title' => t('Dis-Approve Entire Book (This will delete all the examples in the book)'),
      '#prefix' => '<div style="color:red;"><strong>',
      '#suffix' => '</strong></div>',
    );
    $form['run']['delete_book_including_proposal'] = array(
      '#type'  => 'checkbox',
      '#title' => t('Delete Entire Book Including Proposal'),
      '#prefix' => '<div style="color:red;"><strong>',
      '#suffix' => '</strong></div>',
    );

    $form['run']['chapter'] = array(
      '#type' => 'select',
      '#title' => t('Title of the Chapter'),
      '#options' => _list_of_chapters($book_default_value),
      '#default_value' => $chapter_default_value,
      '#tree' => TRUE,
      '#ahah' => array(
        'event'   => 'change',
        'effect' => 'none',
        'path'    => ahah_helper_path(array('run')),
        'wrapper' => 'run-wrapper',
        'progress' => array(
          'type' => 'throbber',
          'message' => t(''),
        ),
      ),
    );
    // if ($chapter_default_value > 0)
    // {
      $form['run']['download_chapter'] = array(
        '#type' => 'item',
        '#value' => l('Download', 'full_download/chapter/' . $chapter_default_value) . ' ' . t('(Download all the approved and unapproved examples of the entire chapter)'),
      );

      $form['run']['approve_chapter'] = array(
        '#type'  => 'checkbox',
        '#title' => t('Approve Entire Chapter'),
      );
      $form['run']['unapprove_chapter'] = array(
        '#type'  => 'checkbox',
        '#title' => t('Pending Review Entire Chapter'),
      );
      $form['run']['disapprove_chapter'] = array(
        '#type'  => 'checkbox',
        '#title' => t('Dis-Approve Entire Chapter (This will delete all the examples in the chapter)'),
        '#prefix' => '<div style="color:red;"><strong>',
        '#suffix' => '</strong></div>',
      );

      $form['run']['example'] = array(
        '#type' => 'select',
        '#title' => t('Example No. (Caption)'),
        '#options' => _list_of_examples($chapter_default_value),
        '#default_value' => $example_default_value,
        '#tree' => TRUE,
        '#ahah' => array(
          'event'   => 'change',
          'effect' => 'none',
          'path'    => ahah_helper_path(array('run')),
          'wrapper' => 'run-wrapper',
          'progress' => array(
            'type' => 'throbber',
            'message' => t(''),
          ),
        ),
      );
    //}
 //}

  /************ START OF $_POST **************/
  if ($_POST)
  {
    if (($book_default_value > 0) && ($chapter_default_value > 0) && ($example_default_value > 0))
    {
      $example_list_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $form_state['values']['run']['example']);
      if ($example_list_q)
      {
        $example_files_rows = array();
        while ($example_list_data = db_fetch_object($example_list_q))
        {
          $example_file_type = '';
          switch ($example_list_data->filetype)
          {
          case 'S' : $example_file_type = 'Source or Main file'; break;
          case 'R' : $example_file_type = 'Result file'; break;
          case 'X' : $example_file_type = 'xcos file'; break;
          default  : $example_file_type = 'Unknown'; break;
          }
          $example_files_rows[] = array(l($example_list_data->filename, 'download/file/' . $example_list_data->id), $example_file_type);
        }

        /* dependency files */
        $dependency_list_q = db_query("SELECT dependency.id as dependency_id, dependency.filename as dependency_filename, dependency.caption as dependency_caption
          FROM {textbook_companion_example_dependency} example_dependency LEFT JOIN {textbook_companion_dependency_files} dependency
          ON example_dependency.dependency_id = dependency.id
          WHERE example_dependency.example_id = %d", $form_state['values']['run']['example']);
          while ($dependency_list_data = db_fetch_object($dependency_list_q))
          {
            $example_file_type = 'Dependency file';
            $temp_caption = '';
            if ($dependency_list_data->dependency_caption)
              $temp_caption = ' (' . $dependency_list_data->dependency_caption . ')';
            $example_files_rows[] = array(l($dependency_list_data->dependency_filename, 'download/dependency/' . $dependency_list_data->dependency_id) . $temp_caption, $example_file_type);
          }

        /* creating list of files table */
        $example_files_header = array('Filename', 'Type');
        $example_files = theme_table($example_files_header, $example_files_rows); 
      }
      $form['run']['download_example'] = array(
        '#type' => 'item',
        '#value' => l('Download Example', 'download/example/' . $example_default_value),
      );
      $form['run']['edit_example'] = array(
        '#type'  => 'item',
        '#value' => l('Edit Example', 'code_approval/editcode/' . $example_default_value),
      );

      $form['run']['example_files'] = array(
        '#type'  => 'item',
        '#title' => 'List of example files',
        '#value' => $example_files,
      );

      $form['run']['approve_example'] = array(
        '#type'  => 'checkbox',
        '#title' => t('Approve Example'),
      );
      $form['run']['unapprove_example'] = array(
        '#type'  => 'checkbox',
        '#title' => t('Pending Review Example'),
      );
      $form['run']['disapprove_example'] = array(
        '#type'  => 'checkbox',
        '#title' => t('Dis-approve Example (This will delete the example)'),
        '#prefix' => '<div style="color:red;"><strong>',
        '#suffix' => '</strong></div>',
      );
    }
  }
  /************ END OF $_POST **************/

  // if ($book_default_value > 0)
  // {
    $form['run']['message'] = array(
      '#type' => 'textarea',
      '#title' => t('If Dis-Approved please specify reason for Dis-Approval'),
    );
  
    $form['run']['submit'] = array(
      '#type' => 'submit',
      '#name' => 'Bulk_approval',
      '#value' => t('Submit')
    );
  // }

  return $form;
}


function bulk_approval_form_submit($form, &$form_state)
{
	global $user;
	$root_path = textbook_companion_path();

	if ($form_state['clicked_button']['#value'] == 'Submit')
	{
		if ($form_state['values']['run']['book'])
			del_book_pdf($form_state['values']['run']['book']);

		if (user_access('bulk manage code'))
		{
      			if ($form_state['values']['run']['approve_book'] == "1")
      			{
			        /* approving entire book */
			        $chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d", $form_state['values']['run']['book']);
			        while ($chapter_data = db_fetch_object($chapter_q))
			        {
				          db_query("UPDATE {textbook_companion_example} SET approval_status = 1, approver_uid = %d WHERE chapter_id = %d AND approval_status = 0", $user->uid, $chapter_data->id);
			        }
			        drupal_set_message(t('Approved Entire Book.'), 'status');

			        /* email */
			        $email_subject = t('Your uploaded examples have been approved');
			        $email_body = t('Your all the uploaded examples for the book have been approved.');

		      	} else if ($form_state['values']['run']['unapprove_book'] == "1") {

			        /* approving entire book */
			        $chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d", $form_state['values']['run']['book']);
			        while ($chapter_data = db_fetch_object($chapter_q))
		        	{
				          db_query("UPDATE {textbook_companion_example} SET approval_status = 0 WHERE chapter_id = %d", $chapter_data->id);
			        }
			        drupal_set_message(t('Pending Review Entire Book.'), 'status');

			        /* email */
			        $email_subject = t('Your uploaded examples have been marked as pending');
			        $email_body = t('Your all the uploaded examples for the book have been marked as pending to be review. You will be able to see the exmaples after they have been approved by one of our reviewers.');

		      	} else if ($form_state['values']['run']['disapprove_book'] == "1") {

				if (!user_access('bulk delete code'))
        			{
          				drupal_set_message(t('You do not have permission to Bulk Dis-Approved and Deleted Entire Book.'), 'error');
			        	return;
        			}

        			if (delete_book($form_state['values']['run']['book']))
        			{
			          	drupal_set_message(t('Dis-Approved and Deleted Entire Book.'), 'status');
        			} else {
          				drupal_set_message(t('Error Dis-Approving and Deleting Entire Book.'), 'error');
        			}

		        	/* email */
        			$email_subject = t('Your uploaded examples have been marked as dis-approved');
        			$email_body = t('Your all the uploaded examples for the whole book have been marked as dis-approved.

Reason for dis-approval:

' . $form_state['values']['run']['message']);

      			} else if ($form_state['values']['run']['delete_book_including_proposal'] == "1") {

        			if (!user_access('bulk delete code'))
        			{
          				drupal_set_message(t('You do not have permission to Bulk Delete Entire Book Including Proposal.'), 'error');
          				return;
        			}

        			/* check if dependency files are present */
        			$dep_q = db_query("SELECT * FROM {textbook_companion_dependency_files} WHERE preference_id = %d", $form_state['values']['run']['book']);
        			if ($dep_data = db_fetch_object($dep_q))
        			{
          				drupal_set_message(t("Cannot delete book since it has dependency files that can be used by others. First delete the dependency files before deleing the Book."), 'error');
          				return;
        			}

        			if (delete_book($form_state['values']['run']['book']))
        			{
          				drupal_set_message(t('Dis-Approved and Deleted Entire Book examples.'), 'status');

          				$dir_path = $root_path . $form_state['values']['run']['book'];
          				if (is_dir($dir_path))
          				{
            					$res = rmdir($dir_path);
            					if (!$res)
            					{
              						drupal_set_message(t("Cannot delete Book directory : " . $dir_path . ". Please contact administrator."), 'error');
              						return;
            					}
          				} else {
            					drupal_set_message(t("Book directory not present : " . $dir_path . ". Skipping deleting book directory."), 'status');
          				}

          				/* deleting preference and proposal */
          				$preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d", $form_state['values']['run']['book']);
          				$preference_data = db_fetch_object($preference_q);
          				$proposal_id = $preference_data->proposal_id;
          				db_query("DELETE FROM {textbook_companion_preference} WHERE proposal_id = %d", $proposal_id);
          				db_query("DELETE FROM {textbook_companion_proposal} WHERE id = %d", $proposal_id);

          				drupal_set_message(t('Deleted Book Proposal.'), 'status');

          				/* email */
          				$email_subject = t('Your uploaded examples including the book proposal have been deleted');
					$email_body = t('Your all the uploaded examples including the book have been deleted permanently.

Reason for deletion:

' . $form_state['values']['run']['message']);

        			} else {
          				drupal_set_message(t('Error Dis-Approving and Deleting Entire Book.'), 'error');
        			}

      			} else if ($form_state['values']['run']['approve_chapter'] == "1") {

        			db_query("UPDATE {textbook_companion_example} SET approval_status = 1, approver_uid = %d WHERE chapter_id = %d AND approval_status = 0", $user->uid, $form_state['values']['run']['chapter']);
        			drupal_set_message(t('Approved Entire Chapter.'), 'status');

        			/* email */
        			$email_subject = t('Your uploaded examples have been approved');
        			$email_body = t('Your all the uploaded examples for the chapter have been approved.');

      			} else if ($form_state['values']['run']['unapprove_chapter'] == "1") {

        			db_query("UPDATE {textbook_companion_example} SET approval_status = 0 WHERE chapter_id = %d", $form_state['values']['run']['chapter']);
        			drupal_set_message(t('Entire Chapter marked as Pending Review.'), 'status');

        			/* email */
        			$email_subject = t('Your uploaded examples have been marked as pending');
        			$email_body = t('Your all the uploaded examples for the chapter have been marked as pending to be review.');

      			} else if ($form_state['values']['run']['disapprove_chapter'] == "1") {

        			if (!user_access('bulk delete code'))
        			{
          				drupal_set_message(t('You do not have permission to Bulk Dis-Approved and Deleted Entire Chapter.'), 'error');
          				return;
        			}

        			if (delete_chapter($form_state['values']['run']['chapter']))
        			{
          				drupal_set_message(t('Dis-Approved and Deleted Entire Chapter.'), 'status');
       	 			} else {
          				drupal_set_message(t('Error Dis-Approving and Deleting Entire Chapter.'), 'error');
        			}

        			/* email */
        			$email_subject = t('Your uploaded example have been marked as dis-approved');
        			$email_body = t('Your uploaded example for the entire chapter have been marked as dis-approved.

Reason for dis-approval:

' . $form_state['values']['run']['message']);

      			} else if ($form_state['values']['run']['approve_example'] == "1") {

        			db_query("UPDATE {textbook_companion_example} SET approval_status = 1, approver_uid = %d WHERE id = %d", $user->uid, $form_state['values']['run']['example']);
        			drupal_set_message(t('Example approved.'), 'status');

        			/* email */
        			$email_subject = t('Your uploaded example has been approved');
        			$email_body = t('Your uploaded example has been approved.');

      			} else if ($form_state['values']['run']['unapprove_example'] == "1") {

        			db_query("UPDATE {textbook_companion_example} SET approval_status = 0 WHERE id = %d", $form_state['values']['run']['example']);
        			drupal_set_message(t('Example marked as Pending Review.'), 'status');

        			/* email */
        			$email_subject = t('Your uploaded example has been marked as pending');
        			$email_body = t('Your uploaded example has been marked as pending to be review.');

      			} else if ($form_state['values']['run']['disapprove_example'] == "1") {

        			if (delete_example($form_state['values']['run']['example']))
        			{
          				drupal_set_message(t('Example Dis-Approved and Deleted.'), 'status');
        			} else {
          				drupal_set_message(t('Error Dis-Approving and Deleting Example.'), 'error');
        			}

        			/* email */
        			$email_subject = t('Your uploaded example has been marked as dis-approved');
        			$email_body = t('Your uploaded example has been marked as dis-approved.

Reason for dis-approval:

' . $form_state['values']['run']['message']);

      			}

      			/****** sending email when everything done ******/
      			if ($email_subject)
      			{
        			$email_to = $user->mail;
        			$param['standard']['subject'] = $email_subject;
        			$param['standard']['body'] = $email_body;
        			if (!drupal_mail('textbook_companion', 'standard', $email_to, language_default(), $param, variable_get('textbook_companion_from_email', NULL), TRUE))
          				drupal_set_message('Error sending email message.', 'error');
      			}

    } else {
      drupal_set_message(t('You do not have permission to bulk manage code.'), 'error');
    }
	}
}

function _list_of_books()
{
  $book_titles = array('0' => 'Please select...');
  $book_titles_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status = 1 OR approval_status = 3 ORDER BY book ASC");
  while ($book_titles_data = db_fetch_object($book_titles_q))
  {
    $book_titles[$book_titles_data->id] = $book_titles_data->book . ' (Written by ' . $book_titles_data->author . ')';
  }
  return $book_titles;
}

function _list_of_chapters($preference_id = 0)
{
  $book_chapters = array('0' => 'Please select...');
  $book_chapters_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d ORDER BY number ASC", $preference_id);
  while ($book_chapters_data = db_fetch_object($book_chapters_q))
  {
    $book_chapters[$book_chapters_data->id] = $book_chapters_data->number . '. ' . $book_chapters_data->name;
  }
  return $book_chapters;
}

function _list_of_examples($chapter_id = 0)
{
  $book_examples = array('0' => 'Please select...');
  $book_examples_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d ORDER BY
    CAST(SUBSTRING_INDEX(number, '.', 1) AS BINARY) ASC,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(number , '.', 2), '.', -1) AS UNSIGNED) ASC,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(number , '.', -1), '.', 1) AS UNSIGNED) ASC", $chapter_id);
  while ($book_examples_data = db_fetch_object($book_examples_q))
  {
    $book_examples[$book_examples_data->id] = $book_examples_data->number . ' (' . $book_examples_data->caption . ')';
  }
  return $book_examples;
}


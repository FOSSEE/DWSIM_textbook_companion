<?php
// $Id$

function upload_examples()
{
  return drupal_get_form('upload_examples_form');
}

function upload_examples_form($form_state)
{
  global $user;

  /************************ start approve book details ************************/
  $proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);
  $proposal_data = db_fetch_object($proposal_q);
  if (!$proposal_data)
  {
    drupal_set_message("Please submit a " . l('proposal', 'proposal') . ".", 'error');
    drupal_goto('');
  }
  if ($proposal_data->proposal_status  != 1)
  {
    switch ($proposal_data->proposal_status )
    {
    case 0:
      drupal_set_message(t('We have already received your proposal. We will get back to you soon.'), 'status');
      drupal_goto('');
      return;
      break;
    case 2:
      drupal_set_message(t('Your proposal has been dis-approved. Please create another proposal ' . l('here', 'proposal') . '.'), 'error');
      drupal_goto('');
      return;
      break;
    case 3:
      drupal_set_message(t('Congratulations! You have completed your last book proposal. You have to create another proposal ' . l('here', 'proposal') . '.'), 'status');
      drupal_goto('');
      return;
      break;
    default:
       drupal_set_message(t('Invalid proposal state. Please contact site administrator for further information.'), 'error');
       drupal_goto('');
       return;
       break;
    }
  }

  $preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);
  $preference_data = db_fetch_object($preference_q);
  if (!$preference_data)
  {
    drupal_set_message(t('Invalid Book Preference status. Please contact site administrator for further information.'), 'error');
    drupal_goto('');
    return;
  }
  /************************ end approve book details **************************/

  /* add javascript for automatic book title, check if example uploaded, dependency selection effects */
  $chapter_name_js = " $(document).ready(function() {
    $('#edit-number').change(function() {
      $.get('" . base_path() . "textbook_companion/ajax/chapter_title/' + $('#edit-number').val() + '/' + " . $preference_data->id . ", function(data) {
        data = data.toString().replace('\t', '');
        $('#edit-name').val(data);
      });
    });
    $('#edit-example-number').change(function() {
      $.get('" . base_path() . "textbook_companion/ajax/example_exists/' + $('#edit-number').val() + '/' + " . $preference_data->id . " + '/' + $('#edit-example-number').val(), function(data) {
        if (!data) {
          alert(data);
        }
      });
    });
    $('#edit-existing-depfile-dep-book-title').change(function() {
      var dep_selected = ''; 
      /* showing and hiding relevant files */
      $('.form-checkboxes .option').hide();
      $('.form-checkboxes .option').each(function(index) {
        var activeClass = $('#edit-existing-depfile-dep-book-title').val();
        if ($(this).children().hasClass(activeClass)) {
          $(this).show();
        }
        if ($(this).children().attr('checked') == true) {
          dep_selected += $(this).children().next().text() + '<br />';
        }
      });
      /* showing list of already existing dependencies */
      $('#existing_depfile_selected').html(dep_selected);
    });

    $('.form-checkboxes .option').change(function() {
      $('#edit-existing-depfile-dep-book-title').trigger('change');
    });
    $('#edit-existing-depfile-dep-book-title').trigger('change');
  });";
  drupal_add_js($chapter_name_js, 'inline', 'header');

  $form['#attributes'] = array('enctype' => "multipart/form-data");

  $form['book_details']['book'] = array(
    '#type' => 'item',
    '#value' => $preference_data->book,
    '#title' => t('Title of the Book'),
  );
  $form['contributor_name'] = array(
    '#type' => 'item',
    '#value' => $proposal_data->full_name,
    '#title' => t('Contributor Name'),
  );

  $form['number'] = array(
    '#type' => 'select',
    '#title' => t('Chapter No'),
    '#options' => array('' => '(Select)', '1' => '1', '2', '3', '4', '5', '6', '7', '8', '9', '10',
                        '11', '12', '13', '14', '15', '16', '17', '18', '19', '20',
                        '21', '22', '23', '24', '25', '26', '27', '28', '29', '30',
                        '31', '32', '33', '34', '35', '36', '37', '38', '39', '40',
                        '41', '42', '43', '44', '45', '46', '47', '48', '49', '50'),
    '#multiple' => FALSE,
    '#size' => 1,
    '#required' => TRUE,
  );
  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Title of the Chapter'),
    '#size' => 40,
    '#maxlength' => 255,
    '#required' => TRUE,
  ); 
  $form['example_number'] = array(
    '#type' => 'textfield',
    '#title' => t('Example No'),
    '#size' => 5,
    '#maxlength' => 10,
    '#description' => t("Example number should be separated by dots only.<br />Example: 1.1.a &nbsp;or&nbsp; 1.1.1"),
    '#required' => TRUE,
  );
  $form['example_caption'] = array(
    '#type' => 'textfield',
    '#title' => t('Caption'),
    '#size' => 40,
    '#maxlength' => 255,
    '#description' => t('Example caption should contain only alphabets, numbers and spaces.'),
    '#required' => TRUE,
  );
  $form['example_warning'] = array(
    '#type' => 'item',
    '#title' => t('You should upload all the files as zip (main or source files, result files, executable file if any)'),
    '#prefix' => '<div style="color:red">',
    '#suffix' => '</div>',
  );

  $form['sourcefile'] = array(
    '#type' => 'fieldset',
    '#title' => t('Main or Source Files'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
  );
  $form['sourcefile']['sourcefile1'] = array(
      '#type' => 'file',
      '#title' => t('Upload main or source file'),
      '#size' => 48,
      '#description' => t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' .
      t('<span style="color:red;">Allowed file extensions : ') . variable_get('textbook_companion_source_extensions', '').'</span>',
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit')
  );

  $form['cancel'] = array(
    '#type' => 'markup',
    '#value' => l(t('Cancel'), 'textbook_companion/code'),
  );
  return $form;
}

function upload_examples_form_validate($form, &$form_state)
{
  if (!check_name($form_state['values']['name']))
    form_set_error('name', t('Title of the Chapter can contain only alphabets, numbers and spaces.'));

  if (!check_name($form_state['values']['example_caption']))
    form_set_error('example_caption', t('Example Caption can contain only alphabets, numbers and spaces.'));

  if (!check_chapter_number($form_state['values']['example_number']))
    form_set_error('example_number', t('Invalid Example Number. Example Number can contain only alphabets and numbers sepereated by dot.'));

  if (isset($_FILES['files']))
  {
    /* check if atleast one source or result file is uploaded */  
    if ( ! ($_FILES['files']['name']['sourcefile1']))
      form_set_error('sourcefile1', t('Please upload source file.'));
    /* check for valid filename extensions */  
    foreach ($_FILES['files']['name'] as $file_form_name => $file_name)
    {
      if ($file_name)
      {
        $allowed_extensions_str = variable_get('textbook_companion_source_extensions', '');
        
        $allowed_extensions = explode(',' , $allowed_extensions_str);
        $temp_extension = end(explode('.', strtolower($_FILES['files']['name'][$file_form_name])));
        //$temp_extension = substr($_FILES['files']['name'][$file_form_name], strripos($_FILES['files']['name'][$file_form_name], '.')); // get file name
  //var_dump($temp_extension); die;
        if (!in_array($temp_extension, $allowed_extensions))
          form_set_error($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
        if ($_FILES['files']['size'][$file_form_name] <= 0)
          form_set_error($file_form_name, t('File size cannot be zero.'));

        /* check if valid file name
        if (!textbook_companion_check_valid_filename($_FILES['files']['name'][$file_form_name]))
         form_set_error($file_form_name, t('Invalid file name specified. Only alphabets, numbers and underscore is allowed as a valid filename.'));*/
      }
    }

  }

  /* add javascript again for automatic book title, check if example uploaded, dependency selection effects */
  $chapter_name_js = " $(document).ready(function() {
    $('#edit-number').change(function() {
      $.get('" . base_path() . "textbook_companion/ajax/chapter_title/' + $('#edit-number').val() + '/' + " . $row->pre_id . ", function(data) {
        $('#edit-name').val(data);
      });
    });
    $('#edit-example-number').change(function() {
      $.get('" . base_path() . "textbook_companion/ajax/example_exists/' + $('#edit-number').val() + '/' + $('#edit-example-number').val(), function(data) {
        if (data) {
          alert(data);
        }
      });
    });
    $('#edit-existing-depfile-dep-book-title').change(function() {
      var dep_selected = ''; 
      /* showing and hiding relevant files */
      $('.form-checkboxes .option').hide();
      $('.form-checkboxes .option').each(function(index) {
        var activeClass = $('#edit-existing-depfile-dep-book-title').val();
        if ($(this).children().hasClass(activeClass)) {
          $(this).show();
        }
        if ($(this).children().attr('checked') == true) {
          dep_selected += $(this).children().next().text() + '<br />';
        }
      });
      /* showing list of already existing dependencies */
      $('#existing_depfile_selected').html(dep_selected);
    });

    $('.form-checkboxes .option').change(function() {
      $('#edit-existing-depfile-dep-book-title').trigger('change');
    });
    $('#edit-existing-depfile-dep-book-title').trigger('change');
  });";
  drupal_add_js($chapter_name_js, 'inline', 'header');
}

function upload_examples_form_submit($form, &$form_state) {
  global $user;

  $root_path = textbook_companion_path();

  /************************ start approve book details ************************/
  $proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE uid = %d ORDER BY id DESC LIMIT 1", $user->uid);
  $proposal_data = db_fetch_object($proposal_q);
  if (!$proposal_data)
  {
    drupal_set_message("Please submit a " . l('proposal', 'proposal') . ".", 'error');
    drupal_goto('');
  }
  if ($proposal_data->proposal_status  != 1)
  {
    switch ($proposal_data->proposal_status )
    {
    case 0:
      drupal_set_message(t('We have already received your proposal. We will get back to you soon.'), 'status');
      drupal_goto('');
      return;
      break;
    case 2:
      drupal_set_message(t('Your proposal has been dis-approved. Please create another proposal ' . l('here', 'proposal') . '.'), 'error');
      drupal_goto('');
      return;
      break;
    case 3:
      drupal_set_message(t('Congratulations! You have completed your last book proposal. You have to create another proposal ' . l('here', 'textbook_companion/proposal') . '.'), 'status');
      drupal_goto('');
      return;
      break;
    default:
       drupal_set_message(t('Invalid proposal state. Please contact site administrator for further information.'), 'error');
       drupal_goto('');
       return;
       break;
    }
  }

  $preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND approval_status = 1 LIMIT 1", $proposal_data->id);
  $preference_data = db_fetch_object($preference_q);
  if (!$preference_data)
  {
    drupal_set_message(t('Invalid Book Preference status. Please contact site administrator for further information.'), 'error');
    drupal_goto('');
    return;
  }
  /************************ end approve book details **************************/

  $preference_id = $preference_data->id;

  $dest_path = $preference_id . '/';
  if (!is_dir($root_path . $dest_path))
    mkdir($root_path . $dest_path);

  /* inserting chapter details */
  $chapter_id = 0;
  $chapter_result = db_query("SELECT * FROM {textbook_companion_chapter} WHERE preference_id = %d AND number = %d", $preference_id, $form_state['values']['number']);
  if (!$chapter_row = db_fetch_object($chapter_result))
  {
    db_query("INSERT INTO {textbook_companion_chapter} (preference_id, number, name) VALUES (%d, '%s', '%s')",
      $preference_id,
      $form_state['values']['number'],
      $form_state['values']['name']
    );
    $chapter_id = db_last_insert_id('textbook_companion_chapter', 'id');
  } else {
    $chapter_id = $chapter_row->id;
    db_query("UPDATE {textbook_companion_chapter} SET name = '%s' WHERE id = %d", $form_state['values']['name'], $chapter_id);
  }

  /*  get example details - dont allow if already example present */
  $cur_example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE chapter_id = %d AND number = '%s'", $chapter_id, $form_state['values']['example_number']);
  if ($cur_example_d = db_fetch_object($cur_example_q))
  {
    if ($cur_example_d->approval_status == 1)
    {
      drupal_set_message(t("Example already approved. Cannot overwrite it."), 'error');
      drupal_goto('textbook_companion/code');
      return;
    } else if ($cur_example_d->approval_status == 0) {
      drupal_set_message(t("Example is under pending review. Delete the example and reupload it."), 'error');
      drupal_goto('textbook_companion/code');
      return;
    } else {
      drupal_set_message(t("Error uploading example. Please contact administrator."), 'error');
      drupal_goto('textbook_companion/code');
      return;
    }
  }

  /* creating directories */
  $dest_path .= 'CH' . $form_state['values']['number'] . '/';
  if (!is_dir($root_path . $dest_path))
    mkdir($root_path . $dest_path);

  $dest_path .= 'EX' . $form_state['values']['example_number'] . '/';
  if (!is_dir($root_path . $dest_path))
    mkdir($root_path . $dest_path);

  /* creating example database entry */
  db_query("INSERT INTO {textbook_companion_example} (chapter_id, number, caption, approval_status, timestamp) VALUES (%d, '%s', '%s', %d, %d)",
    $chapter_id,
    $form_state['values']['example_number'],
    $form_state['values']['example_caption'],
    0,
    time()
  );
  $example_id = db_last_insert_id('textbook_companion_example', 'id');

   /* uploading files */
  foreach ($_FILES['files']['name'] as $file_form_name => $file_name)
  {
    if ($file_name)
    {
      /* checking file type */
      $file_type = 'S';

      if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]))
      {
        drupal_set_message(t("Error uploading file. File !filename already exists.", array('!filename' => $_FILES['files']['name'][$file_form_name])), 'error');
        return;
      }

      /* uploading file */
      if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name]))
      {
        /* for uploaded files making an entry in the database */
        db_query("INSERT INTO {textbook_companion_example_files} (example_id, filename, filepath, filesize, filetype, timestamp)
          VALUES (%d, '%s', '%s', %d, '%s', %d)",
          $example_id,
          $_FILES['files']['name'][$file_form_name],
          $dest_path . $_FILES['files']['name'][$file_form_name],         
          $_FILES['files']['size'][$file_form_name],
          $file_type,
          time()
          );
        drupal_set_message($file_name . ' uploaded successfully.', 'status');
      } else {
        drupal_set_message('Error uploading file : ' . $dest_path . '/' . $file_name, 'error');
      }
    }
  }
  drupal_set_message('Example uploaded successfully.', 'status');

  /* sending email */
  $email_to = $user->mail;
  $param['example_uploaded']['example_id'] = $example_id;
  $param['example_uploaded']['user_id'] = $user->uid;
  if (!drupal_mail('textbook_companion', 'example_uploaded', $email_to, language_default(), $param, variable_get('textbook_companion_from_email', NULL), TRUE))
    drupal_set_message('Error sending email message.', 'error');

  drupal_goto('textbook_companion/code');
}

/******************************************************************************/
/***************************** DELETE EXAMPLE *********************************/
/******************************************************************************/

function _upload_examples_delete()
{
  global $user;
  $root_path = textbook_companion_path();
  $example_id = arg(3);

  /* check example */
  $example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d LIMIT 1", $example_id);
  $example_data = db_fetch_object($example_q);
  if (!$example_data)
  {
    drupal_set_message('Invalid example.', 'error');
    drupal_goto('textbook_companion/code');
    return;
  }
  if ($example_data->approval_status != 0)
  {
    drupal_set_message('You cannnot delete an example after it has been approved. Please contact site administrator if you want to delete this example.', 'error');
    drupal_goto('textbook_companion/code');
    return;
  }

  $chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d LIMIT 1", $example_data->chapter_id);
  $chapter_data = db_fetch_object($chapter_q);
  if (!$chapter_data)
  {
    drupal_set_message('You do not have permission to delete this example.', 'error');
    drupal_goto('textbook_companion/code');
    return;
  }

  $preference_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE id = %d LIMIT 1", $chapter_data->preference_id);
  $preference_data = db_fetch_object($preference_q);
  if (!$preference_data)
  {
    drupal_set_message('You do not have permission to delete this example.', 'error');
    drupal_goto('textbook_companion/code');
    return;
  }

  $proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d AND uid = %d LIMIT 1", $preference_data->proposal_id, $user->uid);
  $proposal_data = db_fetch_object($proposal_q);
  if (!$proposal_data)
  {
    drupal_set_message('You do not have permission to delete this example.', 'error');
    drupal_goto('textbook_companion/code');
    return;
  }

  /* deleting example files */
  if (delete_example($example_data->id))
  {
    drupal_set_message('Example deleted.', 'status');

    /* sending email */
    $email_to = $user->mail;
    $param['example_deleted_user']['book_title'] = $preference_data->book;
    $param['example_deleted_user']['chapter_title'] = $chapter_data->name;
    $param['example_deleted_user']['example_number'] = $example_data->number;
    $param['example_deleted_user']['example_caption'] = $example_data->caption;

    $param['example_deleted_user']['user_id'] = $user->uid;

    if (!drupal_mail('textbook_companion', 'example_deleted_user', $email_to, language_default(), $param, variable_get('textbook_companion_from_email', NULL), TRUE))
      drupal_set_message('Error sending email message.', 'error');
  } else {
    drupal_set_message('Error deleting example.', 'status');
  }

  drupal_goto('textbook_companion/code');
  return;
}

/******************************************************************************/
/************************** GENERAL FUNCTIONS *********************************/
/******************************************************************************/

function _list_of_book_titles()
{
  $book_titles = array('0' => 'Please select...');
  $book_titles_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE approval_status = 1 OR approval_status = 3 ORDER BY book ASC");
  while ($book_titles_data = db_fetch_object($book_titles_q))
  {
    $book_titles[$book_titles_data->id] = $book_titles_data->book . ' (Written by ' . $book_titles_data->author . ')';
  }
  return $book_titles;
}

function _list_of_book_dependency_files()
{
  $book_dependency_files = array();
  $book_dependency_files_class = array();
  $book_dependency_files_q = db_query("SELECT * FROM {textbook_companion_dependency_files} ORDER BY filename ASC");

  while ($book_dependency_files_data = db_fetch_object($book_dependency_files_q))
  {
    $temp_caption = '';
    if ($book_dependency_files_data->caption)
      $temp_caption .= ' (' . $book_dependency_files_data->caption . ')';
    $book_dependency_files[$book_dependency_files_data->id] = l($book_dependency_files_data->filename . $temp_caption, 'download/dependency/' . $book_dependency_files_data->id);
    $book_dependency_files_class[$book_dependency_files_data->id] = $book_dependency_files_data->preference_id;
  }
  return array($book_dependency_files, $book_dependency_files_class);
}

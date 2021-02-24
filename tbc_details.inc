<?php
//  DWSIM TBC display completed Books
function dwsim_tbc_completed_book_proposal_all()
{
	$output = "";
	$query = db_select('textbook_companion_preference', 'pe');
	$query->fields('pe', array('book', 'author', 'publisher', 'year', 'id'));
	$query->leftJoin('textbook_companion_proposal', 'po','pe.proposal_id = po.id');
	$query->fields('po', array('full_name', 'university', 'completion_date'));
	$query->condition('po.proposal_status', 3);
	$query->condition('pe.approval_status', 1);
	$query->orderBy('po.completion_date', 'DESC');
	$result = $query->execute();
	if ($result->rowCount() == 0)
	{
		$output .= "Work has been completed on the following books under the Textbook Companion Project. 
					<span style='color:red;'>The list below is not the books as named but only are the solved example for DWSIM</span>";

	} //$result->rowCount() == 0
	else
	{
		/*$output .= "Total number of completed TBC: " . $result->rowCount() . "<br>";*/
		$output .= "Work has been completed on the following books under the Textbook Companion Project. <br>
					<span style='color:red;'>The list below is not the books as named but only are the solved example for DWSIM.</span>";
		$preference_rows = array();
		$i = 1;
		while ($row = $result->fetchObject())
		{
			$completion_date = date("Y", $row->completion_date);
			$preference_rows[] = array(
				$i,
				l($row->book . ' by ' . $row->author . ', ' . $row->publisher . ', ' . $row->year, 'textbook-companion/textbook-run/' . $row->id),
				$row->full_name,
				$row->university,
				$completion_date
			);
			$i++;
			/*$preference_data->book . " by " . $preference_data->author . ", " . $preference_data->publisher . ", " . $preference_data->year, 'textbook-companion/textbook-run/' . $preference_data->id*/
		} //$row = $result->fetchObject()
		$preference_header = array(
			'No',
			'Title of the Book',
			'Contributor Name',
			'University / Institute',
			'Year of Completion'
		);
		$output .= theme('table', array(
			'header' => $preference_header,
			'rows' => $preference_rows
		));
	}
	return $output;
}


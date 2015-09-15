<?php
class Form 
{
	public $attributes = array(); // an array of attributes to be included in the HTML tag
	public $fields = array(); // array of form fields of type FormField
	public $formSubmitted; // whether or not the form was submitted
	public $method; // form submission method
	public $includeSpambotTest = true; // whether or not to include the anti-spambot test
	public $errors = array(); // validation errors
	public $validationRun = false; // whether or not the validation function has been run
	
	// the following are used for database interaction
	public $mysqli; // mysqli object
	public $table; // the table related to the form
	public $columnInfo; // the array results from a describe table query
	public $primaryKey; // name of primary key field
	public $isEdit = false; // true if a db record is being edited (update), false for new record (insert)
	public $editID; // id of the record being edited, if applicable
	public $SQLWarnings = array(); // an array of warnings regarding values matched to database fields
	
	public function __construct( $initialize = array() )
	{
		if ( ! empty( $initialize['attributes'] ) ) {
			$this->attributes = $initialize['attributes'];
		}
		if ( empty( $this->attributes['method'] ) ) {
			$this->attributes['method'] = 'post'; // default method
		}
		$this->method = $this->attributes['method'];
		if ( empty( $this->attributes['action'] ) ) {
			$this->attributes['action'] = basename( $_SERVER['PHP_SELF'] ); // default action
		}
		$this->setFormSubmitted();
		if ( ! empty( $initialize['mysqli'] ) ) {
			$this->mysqli = $initialize['mysqli'];
		}
		if ( ! empty( $initialize['table'] ) ) {
			$this->table = $initialize['table'];
		}
		$this->setColumnInfo();
	}
	
	// determines whether or not the form has been submitted and sets the formSubmitted variable accordingly
	public function setFormSubmitted()
	{
		if ( ! empty( $this->method ) && strtolower( $this->method ) == 'post' && ! empty( $_POST ) ) {
			$this->formSubmitted = true;
			return;
		} elseif ( ! empty( $this->method ) && strtolower( $this->method ) == 'get' && ! empty( $_GET ) ) {
			$this->formSubmitted = true;
			return;
		}
		$this->formSubmitted = false;
	}
	
	// makes value safe for html output
	public function makeHtmlSafe( $value )
	{
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $val ) {
				$value[$key] = $this->makeHtmlSafe( $val );
			}
			return implode( ', ', $value);
		} else {
			return htmlspecialchars( $value, ENT_QUOTES );
		}
	}
	
	// returns the array of attributes as a string
	public function getAttributeString( $excludes = array() )
	{
		// ensure forms with file inputs have the enctype set
		foreach ( $this->fields as $field ) {
			if ( isset( $field->attributes['type'] ) 
				&& strtolower( $field->attributes['type'] ) == 'file' 
				&& ! isset( $this->attributes['enctype'] ) ) {
				$this->attributes['enctype'] = 'multipart/form-data';
			}
		}
		
		$attributes = '';
		foreach ( $this->attributes as $attribute => $attribute_value ) {
			if ( in_array( $attribute, $excludes ) ) continue;
			$attribute_value = $this->makeHtmlSafe( $attribute_value );
			$attributes .= "{$attribute}='{$attribute_value}' ";
		}
		return $attributes;
	}
	
	// use to add new formField to Form
	public function addField( $values )
	{
		if ( ! empty( $values['attributes']['name'] ) ) {
			$this->fields[$values['attributes']['name']] = new FormField( $values );
			$field = $this->fields[$values['attributes']['name']];
		} else {
			$this->fields[] = new FormField( $values );
			end( $this->fields ); // set pointer to last array item, which should be the item we just added
			$a = each( $this->fields ); // allows us to get the key of the current, now last, item
			$field = $this->fields[$a['key']];
		}
		$field->formSubmitted = $this->formSubmitted;
		$field->method = $this->method;
		$field->setSubmittedValue();
		
		// for database interaction
		$field->mysqli = $this->mysqli;
		if ( isset( $this->columnInfo[$field->dbFieldName] ) ) {
			$field->columnInfo = $this->columnInfo[$field->dbFieldName];
			if ( isset( $field->columnInfo['Key'] ) && strtoupper( $field->columnInfo['Key'] ) == 'PRI' ) {
				$this->primaryKey = $field->dbFieldName;
			}
		}
		$field->sqlSafeValue = $field->makeSqlSafe( $field->value );
	}
	
	// returns anti-spambot input and related script
	public function getAntiSpambotField()
	{
		// anti-spambot test
		return <<<HTML
<input type="text" id="human-check" name="email_check" value="Please delete the contents of this field." size="40" />
<script type="text/javascript">
	var id = document.getElementById('human-check');
	id.value = '';
	id.style.display = 'none';
</script>\n
HTML;
	}
	
	// get html of form validation errors
	public function getErrorsHTML()
	{
		$html = '';
		if ( ! empty( $this->errors ) ) {
			$html .= "<div class='form-errors'>\n";
			foreach ( $this->errors as $error ) {
				$html .= "<div class='form-error'>{$error}</div>\n";
			}
			$html .= "</div>\n";
		}
		return $html;
	}
	
	// returns the default form
	public function getForm( $body = '', $excludes = array() )
	{
		$html = '';
		$attributes = $this->getAttributeString();
		$html .= "<form {$attributes}>\n";
		
		if ( $this->formSubmitted && ! $this->validationRun ) $this->validateForm();
		// include errors
		$html .= $this->getErrorsHTML();
		
		// include required string
		$html .= "<div class='required'>Required fields.</div>\n";
		
		if ( ! empty( $body ) ) {
			$html .= $body;
		} else {
			// get each label and field
			foreach ( $this->fields as $field ) {
				if ( in_array( $field->attributes['name'], $excludes ) ) continue;
				$html .= $field->getFieldWithLabel();
			}
		}
		
		if ( $this->includeSpambotTest ) $html .= $this->getAntiSpambotField();
		
		$html .= "</form>";
		
		return $html;
	} // end function
	
	// Intended for use on "review" page
	// put request data into form to resubmit
	public function getHiddenForm( $confirm_button = null, $excludes = array() )
	{
		$html = '';
		$attributes = $this->getAttributeString();
		$html .= "<form {$attributes}>\n";
		
		//foreach ( $_REQUEST as $request_field => $value ) {
		foreach ( $this->fields as $request_field => $field ) {
			$value = $field->value;
			if ( in_array( $request_field, $excludes ) ) continue;
			$name = htmlspecialchars( $request_field, ENT_QUOTES );
			if ( is_array( $value ) ) {
				foreach ( $value as $v ) {
					$v = htmlspecialchars( $v, ENT_QUOTES );
					$html .= "<input type='hidden' name='{$name}[]' value='{$v}' />\n";
				}
			} else {
				$value = htmlspecialchars( $value, ENT_QUOTES );
				$html .= "<input type='hidden' name='{$name}' value='{$value}' />\n";
			}
		}
			
		if ( $this->includeSpambotTest ) $html .= $this->getAntiSpambotField();
		
		if ( ! empty( $confirm_button ) ) $html .= $confirm_button->getFieldWithLabel();
		
		$html .= "</form>";
		
		return $html;
	} // end function
	
	// returns form data as html or text
	public function getValues( $return_type = 'html', $excludes = array() )
	{
		$text = '';
		$html_email = '';
		$html = "<div class='form-values-html'>\n";
		foreach ( $this->fields as $field ) {
			// typically don't want to include button values
			if ( in_array( $field->attributes['type'], array( 'submit', 'reset', 'button' ) ) ) continue;
			if ( in_array( $field->attributes['name'], $excludes ) ) continue;
			$value = trim( ( is_array( $field->value ) ) ? implode( ', ', $field->value ) : $field->value );
			$text .= "{$field->label}:\n    {$value}\n";
			$label = ( empty( $field->label ) && ! empty( $field->attributes['placeholder'] ) ) ? $field->attributes['placeholder'] : $field->label;
			$label = $this->makeHtmlSafe( $label );
			$html .= "<div><label>{$label}</label> <span class='value'>{$field->htmlSafeValue}</span></div>\n";
			$html_email .= "<p><b>{$label}</b><br>\n&nbsp;&nbsp;&nbsp;&nbsp;<span>{$field->htmlSafeValue}</span></p>\n";
		}
		$html .= "</div>\n";
		switch ( strtolower( $return_type ) ) {
			case 'html':
				return $html;
			case 'html_email':
				return $html_email;
			case 'text':
			default:
				return $text;
		}
	} // end function
	
	// check to see if the submitted antispambot test passes
	public function validateAntiSpambotTest()
	{
		// anti-spambot test
		if ( strtolower( $this->method ) == 'post' && isset( $_POST['email_check'] ) && trim( $_POST['email_check'] ) == '' ) {
				return true;
		} elseif ( strtolower( $this->method ) == 'get' && isset( $_GET['email_check'] ) && trim( $_GET['email_check'] ) == '' ) {
				return true;
		}
		return false;
	}

	// validate the form submission
	public function validateForm()
	{
		$this->errors = array(); // reset errors
		foreach ( $this->fields as $field ) {
			$field->errors = ''; // reset errors
			if ( isset( $field->attributes['readonly'] ) ) continue;
			$result = $field->validateInput();
			if ( $result !== true ) {
				if ( ! empty( $field->attributes['name'] ) ) {
					$this->errors[$field->attributes['name']] = $result;
				} else {
					$this->errors[] = $result;
				}
				$field->errors = $result;
			}
		}
		if ( $this->includeSpambotTest && ! $this->validateAntiSpambotTest() ) {
			$this->errors[] = 'Security Check Failed';
		}
		$this->validationRun = true;
	} // end function
	
	// $files should be an array of files containing info from the $_FILES array
	public function saveUploadedFiles( $dir, $files )
	{
		$errors = array();
		$filenames = array();
		if ( ! is_dir( $dir ) ) {
			if ( ! @mkdir( $dir, 0777, true ) ) {
				$errors[] = "<div class='form-errors'>Can't create file directory {$dir}.</div>\n";
			}
		}
		if ( is_dir( $dir ) && is_writable( $dir ) ) {
			foreach( $files as $file ) {
				// if filename already exists, rename current file
				$i = 0;
				$name = $file['name'];
				$pieces = pathinfo( $file['name'] );
				$ext = ( isset( $pieces['extension'] ) ) ? ".{$pieces['extension']}" : '';
				while ( file_exists( "{$dir}/{$name}" ) && $i < 10000 ) {
					$name = "{$pieces['filename']}{$i}{$ext}";
					$i++;
				}
				
				// move temp file to new location
				if ( ! move_uploaded_file( $file['tmp_name'], "{$dir}/{$name}" ) ) {
					$errors[] = "<div class='form-errors'>There was an error attempting to save an uploaded file.</div>\n";
				} else {
					$filenames[] = $name;
				}
			} // end loop for each file
		} else {
			$errors[] = "<div class='form-errors'>Can't write to file directory.</div>\n";
		}
		
		return array( 'errors' => $errors, 'filenames' => $filenames );
	} // end function
	
	/* the following methods are for database interaction */
	
	// gets the info for columns in table and saves this info in object property
	public function setColumnInfo()
	{
		if ( empty( $this->mysqli ) || empty( $this->table ) ) return false;
		
        $result = $this->mysqli->query( "DESCRIBE `{$this->table}`" );
		while ( $column = $result->fetch_array() ) {
		
			// separate type, length, and other values from type field
			$column['Unsigned'] = stripos( $column['Type'], ' unsigned' );
			$column['Type'] = str_replace( ' unsigned', '', $column['Type'] );
			
			// extract length
			$begin = strpos( $column['Type'], '(' );
			$end = strrpos( $column['Type'], ')' );
			// if both are found
			if ( $begin && $end ) {
				$column['Length'] = substr( $column['Type'], $begin + 1, $end - $begin - 1 );
			} else {
				$column['Length'] = null;
			}
			$column['Length'] = str_replace( '\'', '', $column['Length'] ); // remove single quotes?
			
			// set Type to contain only the type
			$length = strpos( $column['Type'], '(' );
			if ( $length ) $column['Type'] = substr( $column['Type'], 0, $length );
			
			// set the primary key
			if ( isset( $column['Key'] ) && strtoupper( $column['Key'] ) == 'PRI' ) {
				$this->primaryKey = $column['Field'];
			}
			
			// save column info
			$this->columnInfo[$column['Field']] = $column;
			
		} // end loop for each column
		
		$result->free();
		return true;
	} // end function
	
	// get a record from the database; sets isEdit and editID if not already set 
	public function getDBData( $id = null )
	{
		if ( empty( $this->primaryKey ) || empty( $this->table ) ) return false;
		if ( empty( $id ) ) $id = $this->editID;
		if ( empty( $id ) ) return false;
		if ( $id != $this->editID ) $this->editID = $this->mysqli->real_escape_string( $id );
		$query = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = '{$this->editID}'";
		$result = $this->mysqli->query( $query );
		$record = ( $result ) ? $result->fetch_array() : $result;
		$this->isEdit = ( ! empty( $record ) ) ? true : false;
		return $record;
	} // end function
	
	// creates an insert or update query of the submitted form data
	public function buildQuery( $excludes = array(), $sql = array() )
	{
		$CommonData = new CommonData();
		$_PATTERNS = $CommonData->getCommonRegexPatterns();

		$query = ( ( $this->isEdit ) ? 'UPDATE' : 'INSERT INTO' ) . " `{$this->table}` SET ";
		
		foreach ( $this->fields as $field ) {
			// typically don't want to include button values
			if ( isset( $field->attributes['type'] ) && in_array( $field->attributes['type'], array( 'submit', 'reset', 'button' ) ) ) continue;
			// don't attempt to update disabled fields as they won't have any value submitted and could overwrite data
			if ( array_key_exists( 'disabled', $field->attributes ) ) continue; 
			if ( isset( $field->attributes['name'] ) && in_array( $field->attributes['name'], $excludes ) ) continue;
			
			if ( is_null( $field->sqlSafeValue ) ) {
				if ( ! empty( $field->columnInfo ) && $field->columnInfo['Null'] == 'YES' ) {
					// if there is no value which was submitted, set field to null
					// we do this because MySQL will convert blank entries for number and date fields into values
					$sql[] = "`{$field->dbFieldName}` = NULL";
				}
			} else {
				if ( ! empty( $field->columnInfo['Type'] ) ) {
					switch ( strtoupper( $field->columnInfo['Type'] ) ) {
						// these are types which can accept numbers
						case 'BIT':
						case 'BOOL':
						case 'BOOLEAN':
						case 'TINYINT':
						case 'SMALLINT':
						case 'MEDIUMINT':
						case 'INT':
						case 'INTEGER':
						case 'BIGINT':
						case 'FLOAT':
						case 'DOUBLE':
						case 'DEC':
						case 'DECIMAL':
						case 'NUMERIC':
						case 'FIXED':
						case 'DATE':
						case 'DATETIME':
						case 'TIMESTAMP':
						case 'TIME':
						case 'YEAR':
							//if ( is_numeric( $field->sqlSafeValue ) ) {
							if ( is_numeric( $field->sqlSafeValue ) || preg_match( "/^{$_PATTERNS['sql']['binary_notation']}$/", $field->value ) ) {
								$sql[] = "`{$field->dbFieldName}` = {$field->sqlSafeValue}";
								continue 2;
							}
							break;
					} // end switch
				} // end type set
				
				// default syntax
				$sql[] = "`{$field->dbFieldName}` = '{$field->sqlSafeValue}'";
				
			} // end not null
		} // end loop for each field
		
		$query .= implode( ", \n", $sql );
		
		if ( $this->isEdit ) {
			$edit_id = $this->mysqli->real_escape_string( $this->editID );
			$query .= " WHERE `{$this->primaryKey}` = '{$edit_id}'";
		}
		
		return $query;
	} // end function
	
	// checks the submitted data to make sure it's valid for inserting into the database
	// mysql will accept most invalid values so long as the query syntax is correct
	// mysql will change invalid values into a valid value
	// this function checks more for warnings of when a value is invalid as opposed to errors which prevent the query from executing
	public function validateSQLData( $excludes = array() ) 
	{
		foreach ( $this->fields as $field ) {
			// typically don't want to include button values
			if ( isset( $field->attributes['type'] ) && in_array( $field->attributes['type'], array( 'submit', 'reset', 'button' ) ) ) continue;
			if ( array_key_exists( 'disabled', $field->attributes ) ) continue; 
			if ( isset( $field->attributes['name'] ) && in_array( $field->attributes['name'], $excludes ) ) continue;
			$result = $field->validateSQLValue();
			if ( $result !== true ) {
				if ( is_array( $result ) ) {
					$this->SQLWarnings = array_merge( $this->SQLWarnings, $result );
				} else {
					$this->SQLWarnings[] = $result;
				}
				$field->SQLWarnings = $result;
			}
		}
		return true;
	} // end function
	
} // end class

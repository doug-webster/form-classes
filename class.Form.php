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
	public $useLabelsForPlaceholders = false;
	public $usePlaceholdersForLabels = false;
	
	// the following are used for database interaction
	public $mysqli; // mysqli object
	public $table; // the table related to the form
	public $columnInfo; // the array results from a describe table query
	public $primaryKey; // name of primary key field
	public $isEdit = false; // true if a db record is being edited (update), false for new record (insert)
	public $editID; // id of the record being edited, if applicable
	public $record = array(); // an array of data to use for form values initially
	public $SQLWarnings = array(); // an array of warnings regarding values matched to database fields
	
	public function __construct( $initialize = array() )
	{
		if ( ! empty( $initialize['attributes'] ) )
			$this->attributes = $initialize['attributes'];
		if ( empty( $this->attributes['class'] ) )
			$this->attributes['class'] = 'form-class';
		if ( empty( $this->attributes['method'] ) )
			$this->attributes['method'] = 'post'; // default method
		$this->method = $this->attributes['method'];
		if ( empty( $this->attributes['action'] ) )
			$this->attributes['action'] = basename( $_SERVER['PHP_SELF'] ); // default action
		$this->setFormSubmitted();
		if ( ! empty( $initialize['mysqli'] ) )
			$this->mysqli = $initialize['mysqli'];
		if ( ! empty( $initialize['table'] ) )
			$this->table = $initialize['table'];
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
			return $this->implode_recursive( ', ', $value);
		} else {
			return htmlspecialchars( $value, ENT_QUOTES );
		}
	}
	
	// implode a multi-demensional array
	protected function implode_recursive( $separator, $var )
	{
		if ( ! is_array( $var ) ) return (string)$var;

		$return = '';
		foreach ( $var as $k => $val ) {
			if ( is_array( $val ) )
				$return .= $this->implode_recursive( $separator, $val );
			else
				$return .= (string)$val;
			$return .= $separator;
		}
		return trim( $return, $separator );
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
		if ( $field->attributes['type'] != 'password' ) {
			// set value of "value" attribute to corresponding record value if present
			if ( ! isset( $field->attributes['value'] ) )
				$field->attributes['value'] = '';
			if ( ! empty( $field->dbFieldName ) && isset( $this->record[$field->dbFieldName] ) ) {
				if ( in_array( $field->attributes['type'], array( 'checkbox', 'radio' ) ) && count( $field->options ) <= 1 ) {
					if ( ! empty( $this->record[$field->dbFieldName] ) )
						$field->attributes['checked'] = 'checked';
					else
						unset( $field->attributes['checked'] );
				} else {
					$v = $this->record[$field->dbFieldName];
					if ( ! empty( $v ) || $v === '0' )
						$field->attributes['value'] = $v;
				}
			}
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
				if ( isset( $field->attributes['name'] ) && in_array( $field->attributes['name'], $excludes ) ) continue;
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
			
			$value = $field->value;
			if ( is_array( $field->value ) ) {
				$value = $field->implode_recursive( ', ', $field->value );
			} else if ( is_string( $field->value ) && ! empty( $field->options[$field->value] ) ) {
				// for display, use option value rather than key
				$value = $field->options[$field->value];
			}
			$value = trim( $value );
			$htmlSafeValue = $this->makeHtmlSafe( $value );
			
			$label = ( empty( $field->label ) && ! empty( $field->attributes['placeholder'] ) )
				? $field->attributes['placeholder'] : $field->label;
			$text .= rtrim($label, ':') . ":\n    {$value}\n";
			$label = $this->makeHtmlSafe( $label );
			$html .= "<div><label>{$label}</label> <span class='value'>{$htmlSafeValue}</span></div>\n";
			$html_email .= "<p><b>{$label}</b><br>\n&nbsp;&nbsp;&nbsp;&nbsp;<span>{$htmlSafeValue}</span></p>\n";
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
	
	// returns CSS for form
	public static function getFormCSS()
	{
		return <<<CSS
form.form-class .required {}
form.form-class .required:after {content: "*";}
form.form-class .attention {color: #CC0000;}
.form-errors {margin: 10px auto; color: #CC0000;}
.form-errors .label {font-weight: bold;}
form.form-class .form-input-box {font-size: 1em; line-height: 1.5em; margin-top: 1em; margin-bottom: 1em; white-space: nowrap;}
form.form-class .aligned {margin-left: 126.5px;}
form.form-class .form-options {display: inline-block;}
form.form-class .form-options > input {margin-left: 0;}

/* labels */
form.form-class label:not(.checkbox):not(.radio) {display: -moz-inline-stack; display: inline-block; margin: 0 5px 0 0; padding: 0; text-align: right; width: 120px; white-space: normal; vertical-align: top;}
/* for radio and checkbox labels */
form.form-class label.inline:not(.checkbox):not(.radio) {display: inline; text-align: left; width: auto;}

/* inputs (inc. select and textarea) */
.form-class input, .form-class select, .form-class textarea {max-width: 100%;}
.form-class textarea {height: 100px; width: 350px; vertical-align: top;}
.form-class input[readonly], .form-class textarea[readonly], .form-class select[readonly], 
.form-class input[disabled], .form-class textarea[disabled], .form-class select[disabled] {background-color: #EEEEEE; color: #666666; border: thin solid #AAAAAA;}
.form-class input[type="file"] {background-color: white; border: thin solid #888; padding: 0;} /*border-style: inset; border-width: thin;*/
.form-class .form-input-box.file.disabled, .form-class input[type="file"][readonly], .form-class input[type="file"][disabled] {display: none;}

/* responsive */
@media (max-width: 590px) /* 17px discrepancy */ {
	form.form-class label {width: auto; display: block; text-align: left;}
	.form-class .aligned {margin-left: auto;}
}

CSS;
	}
	
	// the following methods are for database interaction ---------------------
	
	// gets the info for columns in table and saves this info in object property
	public function setColumnInfo()
	{
		if ( empty( $this->mysqli ) || empty( $this->table ) ) return false;
		
        $result = $this->mysqli->query( "DESCRIBE `{$this->table}`" );
		if ( $result === false ) return false;
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
		if ( $result )
			$this->setRecord( $record );
		return $record;
	} // end function
	
	public function setRecord( $record )
	{
		$this->record = $record;
		$this->isEdit = ( ! empty( $record ) ) ? true : false;
	}
	
	// creates an insert or update query of the submitted form data
	public function buildQuery( $excludes = array(), $sql = array() )
	{
		$_PATTERNS = CommonData::getCommonRegexPatterns();

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

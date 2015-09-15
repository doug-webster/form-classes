<?php
class FormField extends Form
{
	public $label; // the text label for the field
	public $attributes = array(); // an array of attributes to be included in the HTML tag
	public $options = array(); // for radio, checkbox, and select inputs, an array of value => option pairs
	public $value; // raw value submitted, if present
	public $htmlSafeValue; // the value made safe for output into html attributes or textareas
	public $trim = true; // whether or not to trim value
	public $note; // a note with further instructions about the input
	public $disallowed_file_extensions = array( '.exe', '.dll', '.js' ); // reject file uploads of these types
	public $allowed_file_extensions = array(); // limit file uploads to these types
	
	// the following are used for database interaction
	public $mysqli; // mysqli object
	public $dbFieldName; // name of the matching column (field) in the database table
	public $sqlSafeValue; // value made safe for use in sql queries
	public $columnInfo; // the array results from a describe table query for specific column associated with this form field:
		// Field: (field name)
		// Type: (field type)
		// Length: (length or values if enum or set)
		// Null: YES|NO
		// Key: PRI, index or unique
		// Default: (default value)
		// Extra: auto_increment, ?
	
	
	public function __construct( $initialize = array() )
	{
		if ( ! empty( $initialize['label'] ) ) {
			$this->label = trim( $initialize['label'] );
		}
		if ( ! empty( $initialize['attributes'] ) ) {
			$this->attributes = $initialize['attributes'];
		}
		if ( ! empty( $initialize['options'] ) ) {
			$this->options = $initialize['options'];
		}
		if ( ! empty( $initialize['note'] ) ) {
			$this->note = $initialize['note'];
		}
		if ( ! empty( $initialize['dbFieldName'] ) ) {
			$this->dbFieldName = $initialize['dbFieldName'];
		} else {
			if ( ! empty( $this->attributes['name'] ) ) {
				// default to same name as form input name
				$this->dbFieldName = $this->attributes['name'];
			}
		}
	}
	
	// remove slashes if magic quotes is on
	protected function reverse_magic_quotes( &$string )
	{
		if ( get_magic_quotes_runtime() || get_magic_quotes_gpc() ) {
			$string = stripslashes( $string );
		}
	}
	
	// apply trim directly to the submitted variable
	private function trimd( &$string )
	{
		$string = trim( $string );
	}
	
	// apply strtolower directly to submitted variable
	private function strtolowerd( &$string )
	{
		$string = strtolower($string);
	}

	// PHP converts periods and spaces in names to underscores, which can cause
	// trouble finding the values. This function attempts to convert them back
	public function updateRequestGlobals()
	{
		if ( $this->formSubmitted && ! empty( $this->attributes['name'] ) ) {
			if ( ! empty( $this->method ) ) {
				$name = $this->attributes['name'];
				$alt_name = preg_replace( '/ |\./', '_', $name, -1, $count );
				if ( $count ) {
					if ( isset( $_REQUEST[$alt_name] ) ) {
						$_REQUEST[$name] = $_REQUEST[$alt_name];
						unset( $_REQUEST[$alt_name] );
					}
					if ( strtolower( $this->method ) == 'get' && isset( $_GET[$alt_name] ) )  {
						$_GET[$name] = $_GET[$alt_name];
						unset( $_GET[$alt_name] );
					} elseif ( strtolower( $this->method ) == 'post' && isset( $_POST[$alt_name] ) ) {
						$_POST[$name] = $_POST[$alt_name];
						unset( $_POST[$alt_name] );
					}
				}
			}
		}
	}
	
	// sets $this->value to what was submitted if possible
	public function setSubmittedValue()
	{
		$this->updateRequestGlobals();
		if ( $this->formSubmitted && ! empty( $this->attributes['name'] ) ) {
			if ( ! empty( $this->method ) ) {
				$name = $this->attributes['name'];
				// if [] found in name, remove
				if( $count = preg_match_all( '/\[.*?\]/', $name, $matches ) ) {
					$this->value = null;
					if ( ! empty( $matches[0] ) ) {
						$name = str_replace( $matches[0], '', $name );
					}
				}
				// set value
				if ( strtolower( $this->method ) == 'get' && isset( $_GET[$name] ) )  {
					$this->value = $_GET[$name];
				} elseif ( strtolower( $this->method ) == 'post' && isset( $_POST[$name] ) ) {
					$this->value = $_POST[$name];
				}
				// if specific array indexes are imbedded in name, check array for these keys
				if ( ! empty( $matches[0] ) ) {
					$i = 0;
					while ( is_array( $this->value ) && ! empty( $matches[0][$i] ) && $i < $count ) {
						$key = str_replace( array( '[', ']' ), '', $matches[0][$i] );
						$this->value = $this->value[$key];
						$i++;
					}
				}
				// clean value
				if ( is_array( $this->value ) ) {
					if ( $this->trim ) array_walk_recursive( $this->value, array( $this, 'trimd' ) );
					array_walk_recursive( $this->value, array( $this, 'reverse_magic_quotes' ) );
				} else {
					if ( $this->trim ) $this->trimd( $this->value );
					$this->reverse_magic_quotes( $this->value );
				}
				$this->htmlSafeValue = $this->makeHtmlSafe( $this->value );
				// in case attribute value not initially set
				// value shouldn't be set for select, checkbox, and radio inputs
				// don't assign value so that initial value is kept
				if ( ! in_array( $this->attributes['type'], array( 'select', 'checkbox', 'radio' ) ) && ! isset( $this->attributes['value'] ) ) {
					$this->attributes['value'] = '';
				}
			}
		}
	}
	
	// returns the array of attributes as a string
	public function getAttributeString( $excludes = array(), $name_multiple = false )
	{
		$attributes = '';
		foreach ( $this->attributes as $attribute => $attribute_value ) {
			if ( in_array( $attribute, $excludes ) ) continue;
			if ( is_array( $attribute_value ) ) continue; // shouldn't be an array, but just in case
			// if form submitted, use submitted value rather than initial value
			if ( $attribute == 'value' && $this->formSubmitted && ! isset( $this->attributes['disabled'] ) && ! in_array( $this->attributes['type'], array( 'checkbox', 'radio' ) ) ) $attribute_value = $this->value;
			$attribute_value = $this->makeHtmlSafe( $attribute_value );
			// adding '[]' allows for multiple checkboxes to be captured as an array by PHP
			if ( $attribute == 'name' && ( $name_multiple || isset( $this->attributes['multiple'] ) ) ) $attribute_value .= '[]';
			$attributes .= "{$attribute}='{$attribute_value}' ";
		}
		return $attributes;
	}
	
	// returns true or false if the submitted value is selected (or checked)
	public function isOptionSelected( $option_value )
	{
		$value = ( $this->formSubmitted && ! isset( $this->attributes['disabled'] ) ) ? $this->value : ( isset( $this->attributes['value'] ) ? $this->attributes['value'] : '' );
		$option_value = trim( $option_value );
		if ( $value != null ) {
			// check for array since multiple options can be selected (if multiple attribute set)
			if ( ( is_array( $value ) && in_array( $option_value, $value ) ) 
				|| ( ! is_array( $value ) && $value == $option_value ) ) {
				return true;
			}
		}
		return false;
	}
	
	// attempts to format a string as a phone number; returns false on error
	public function formatPhoneNumber( $number )
	{
		$phone = explode( 'x', preg_replace('/[^0-9x]+/', '', strtolower( $number ) ) ); // remove everything except for digits and "x"
		$ext = ( ! empty( $phone[1] ) ) ? $phone[1] : ''; // assume anything after an x is an extension
		$phone = $phone[0];
		if ( strpos( $phone, '1' ) === 0 ) {
			// remove '1' from beginning of number
			$phone = substr( $phone, 1 );
		}
		// a valid North American phone number ought to be ten digits at this point
		if ( strlen( $phone ) != 10 ) return false;
		$areaCode = substr($phone, 0, 3);
		$prefix = substr($phone, 3, 3);
		$digits = substr($phone, 6, 4);
		return "{$areaCode}-{$prefix}-{$digits}" . ( ( ! empty( $ext ) ) ? " ext. {$ext}" : '' );
	}
	
	// returns the input field
	public function getField()
	{
		$this->setId();
		$html = '';
		if ( empty( $this->attributes['type'] ) ) return;
		switch ( $this->attributes['type'] ) {
			case 'textarea':
				$attributes = $this->getAttributeString( array( 'type', 'value' ) );
				if ( $this->formSubmitted && ! isset( $this->attributes['disabled'] ) ) {
					$attribute_value = $this->htmlSafeValue;
				} else {
					$attribute_value = ( isset( $this->attributes['value'] ) ) ? $this->makeHtmlSafe( $this->attributes['value'] ) : '';
				}
				$html .= "<textarea {$attributes}>{$attribute_value}</textarea>\n";
				break;
				
			case 'select':
				if ( ! isset( $this->options ) || ! is_array( $this->options ) ) break;
				$attributes = $this->getAttributeString( array( 'type', 'value', 'placeholder' ) );
				$html .= "<select {$attributes}>\n";
				$placeholder = ( isset( $this->attributes['placeholder'] ) ) ? $this->makeHtmlSafe( $this->attributes['placeholder'] ) : '';
				if ( ! isset( $this->attributes['required'] ) || ! empty( $placeholder ) ) {
					$html .= "<option value=''>{$placeholder}</option>\n";
				}
				
				foreach ( $this->options as $option_value => $option_text ) {
					$selected = $this->isOptionSelected( $option_value ) ? 'selected="selected"' : '';
					$option_value = $this->makeHtmlSafe( $option_value );
					$option_text = $this->makeHtmlSafe( $option_text );
					$html .= "<option value='{$option_value}' {$selected}>{$option_text}</option>\n";
				}
				$html .= "</select>\n";
				break;
				
			case "checkbox":
				if ( empty( $this->options ) || ! is_array( $this->options ) || ( is_array( $this->options ) && count( $this->options ) == 1 ) ) {
				// if options aren't set, create a single input
					$checked = '';
					if ( $this->formSubmitted && ! isset( $this->attributes['disabled'] ) ) {
						if ( ! empty( $this->value ) ) {
							$checked = 'checked="checked"';
						} else {
							if ( isset( $this->attributes['checked'] ) ) unset( $this->attributes['checked'] );
						}
					}
					$attributes = $this->getAttributeString();
					$html .= "<input {$attributes} {$checked} />\n";
					break;
				}
				// if checkbox has options, it will continue into the following process shared by radio inputs
			case "radio":
				if ( empty( $this->options ) || ! is_array( $this->options ) ) break;
				// allow for multiple checkboxes or radio options under one primary label
				$name_multiple = false;
				$excludes = array( 'id', 'value' );
				if ( $this->attributes['type'] == 'checkbox' && count( $this->options ) > 1 ) {
					$name_multiple = true;
					// if required set for checkboxes, will require every one to be selected - not what we want here
					$excludes[] = 'required';
				}
				$attributes = $this->getAttributeString( $excludes, $name_multiple );
				$id = ( ! empty( $this->attributes['id'] ) ) ? $this->attributes['id'] : '';
				
				$html .= "<div class='form-options'>\n";
				$i = 0;
				foreach ( $this->options as $option_value => $option_text ) {
					++$i;
					//$option_value = ( $option_value != '' ) ? $option_value : '(see below)'; // used in conjunction with the custom user value
					$checked = $this->isOptionSelected( $option_value ) ? 'checked="checked"' : '';
					$option_text = $this->makeHtmlSafe( $option_text );
					$option_value = $this->makeHtmlSafe( $option_value );
					$html .= "<input {$attributes} {$checked} id='{$id}-{$i}' value='{$option_value}' />\n";
					if ( $option_text != '' ) {
						$html .= "<label for='{$id}-{$i}' class='inline'>{$option_text}</label><br />\n";
					} else {
					// a blank option indicates custom user input--it allows them to specify an 'other' option
						// determine if a custom value has been set
						$value = ( isset( $_REQUEST["{$this->attributes['name']}_custom"] ) ) ? $this->makeHtmlSafe( $_REQUEST["{$this->attributes['name']}_custom"] ) : '';
						$name = $this->makeHtmlSafe( $this->attributes['name'] );
						$html .= "<input type='text' name='{$name}_custom' value='{$value}' placeholder='Other (please specify)' />\n";
					}
				}
				$html .= "</div>\n";
				break;
				
			case "submit":
			case "reset":
			case "button":
				// make these all buttons rather than inputs
				$attributes = $this->getAttributeString();
				$label = $this->makeHtmlSafe( $this->label );
				$html .= "<button {$attributes}>{$label}</button>";
				break;
				
			case "file":
			
			case "text":
			case "color":
			case "email":
			case "search":
			case "url":
			case "date":
			case "datetime":
			case "datetime-local":
			case "month":
			case "week":
			case "time":
			case "number":
			case "tel":
			case "range":
			case "password":
			case "hidden":
			default:
				$attributes = $this->getAttributeString();
				$html .= "<input {$attributes} />\n";
				break;
		} // end switch
		
		return $html;
	} // end function

	// set id if not explicitly set
	public function setId()
	{
		if ( empty( $this->attributes['id'] ) && ! empty( $this->attributes['name'] ) ) {
			$replace = '_';
			$name = $this->attributes['name'];
			$name = preg_replace( '/[^0-9a-zA-Z]+/', $replace, $name );
			$name = trim( $name, $replace );
			$name = $this->makeHtmlSafe( $name );
			$this->attributes['id'] = "i_{$name}";
		}
	}
	
	// return an html label
	public function getLabel()
	{
		$this->setId();
		$label = $this->makeHtmlSafe( $this->label );
		$classes[] = ( isset( $this->attributes['required'] ) ) ? 'required' : '';
		$classes[] = ( ! empty( $this->errors ) ) ? 'attention' : '';
		$classes = implode( ' ', $classes );
		$id = ( isset( $this->attributes['id'] ) ) ? $this->attributes['id'] : '';
		return "<label for='{$id}' class='{$classes}'>{$label}</label>\n";
	}
	
	// return the field along with label and html wrapper
	public function getFieldWithLabel( $input = '' )
	{
		$html = '';
		$type = isset( $this->attributes['type'] ) ? strtolower( $this->attributes['type'] ) : '';
		
		$this->setId();
		
		// hidden fields don't need labels and wrappers
		if ( $type == 'hidden' ) {
			return ( ! empty( $input ) ) ? $input : $this->getField();
		}
		
		$classes = array();
		if ( $type == 'file' ) $classes[] = 'file-input';
		if ( ! empty( $this->errors ) ) $classes[] = 'attention';
		if ( isset( $this->attributes['disabled'] ) ) $classes[] = 'disabled';
		$classes = implode( ' ', $classes );
		$html .= "<div class='form-input-box {$classes}" . ( ( in_array( $type, array( 'submit', 'reset', 'button' ) ) ) ? ' aligned' : '') . "' id='form_" . $this->attributes['id'] . "'>\n";
		
		if ( ! in_array( $type, array( 'submit', 'reset', 'button' ) ) ) {
			$html .= $this->getLabel();
		}
		
		if ( ! empty( $input ) ) {
			$html .= $input;
		} else {
			$html .= '<span class="input-wrapper">' . $this->getField() . '</span>';
			if ( ! empty( $this->note ) ) {
				$html .= '<span class="input-note">' . $this->makeHtmlSafe( $this->note ) . '</span>';
			}
		}
		
		$html .= "</div>\n";
		
		return $html;
	}
	
	// determines whether or not the input receieved is valid
	// $strict = require inputs to exactly match the patterns defined in the HTML spec
	// $change = if true the function will attempt to modify an invalid value such that it becomes valid
	// returns true if valid or an error message if invalid
	public function validateInput( $strict = false, $change = true )
	{
		$CommonData = new CommonData();
		$_PATTERNS = $CommonData->getCommonRegexPatterns();
		$_COLOR_NAMES = $CommonData->getCSSColorNames();
		$_FILE_UPLOAD_ERROR_CODES = $CommonData->getFileUploadErrorCodes();
		$label = ( empty( $this->label ) && ! empty( $this->attributes['placeholder'] ) ) ? $this->attributes['placeholder'] : $this->label;
		$label = '<span class="label">' . $this->makeHtmlSafe( $label ) . '</span>';
		$required = isset( $this->attributes['required'] ) ? true : false;
		$type = ( ! empty( $this->attributes['type'] ) ) ? strtolower( $this->attributes['type'] ) : '';
		$match_value = ( ! is_array( $this->value ) ) ? trim( $this->value ) : implode( ', ', $this->value );
		
		// if required, check to make sure value exists and is not blank
		// file types won't have a value at this point
		if ( ( $this->value == null || $this->value == '' ) && $type != 'file' ) {
			if ( $required && ! isset( $this->attributes['disabled'] ) ) {
				return "{$label} is a required field.";
			} else {
				return true; // don't check patterns if blank and not required
			}
		}
		
		switch ( $type ) {
			case "submit":
			case "reset":
			case "button":
			case "hidden":
				// these shouldn't need to be validated
				return true;
				
			// the following HTML 5 inputs should match to specific patterns (patterns in $_PATTERNS)
			case 'datetime':
				$date_format = DATE_RFC3339;
				$human_readable = 'YYYY-MM-DD"T"HH:MM:SS"Z" or "+/-HH:MM"';
				break;
			case 'datetime-local':
				$date_format = 'Y-m-d\TH:i:s';
				$human_readable = 'YYYY-MM-DD"T"HH:MM:SS';
				break;
			case 'date':
				$date_format = 'Y-m-d';
				$human_readable = 'YYYY-MM-DD';
				break;
			case 'time':
				$date_format = 'H:i:s';
				$human_readable = 'HH:MM:SS';
				break;
			case 'month':
				$date_format = 'Y-m';
				$human_readable = 'YYYY-MM';
				break;
			case 'week':
				$date_format = 'Y-\WW';
				$human_readable = 'YYYY-"W"WW';
				break;
				
			case 'tel':
				break;
			case 'email':
				break;
			case 'url':
				break;
				
			case 'color':
				if ( $strict ) {
					$color_type = 'hex_strict';
				} elseif ( strpos( $match_value, '#' ) === 0 ) {
					$color_type = 'hex';
				} elseif ( stripos( $match_value, 'rgba' ) === 0 ) {
					$this->value = strtolower( $this->value );
					$color_type = 'rgba';
				} elseif ( stripos( $match_value, 'rgb' ) === 0 ) {
					$this->value = strtolower( $this->value );
					$color_type = 'rgb';
				} elseif ( stripos( $match_value, 'hsla' ) === 0 ) {
					$this->value = strtolower( $this->value );
					$color_type = 'hsla';
				} elseif ( stripos( $match_value, 'hsl' ) === 0 ) {
					$this->value = strtolower( $this->value );
					$color_type = 'hsl';
				} elseif ( array_key_exists( strtolower( $this->value ), $_COLOR_NAMES ) ) {
					// valid value; no action necessary here
				} else {
					return "{$label} is not a recognized color value.";
				}
				break;
					
			// the following inputs generally have no special pattern to match
			case 'search':
			case 'textarea':
			case 'text':
			case 'password':
			case 'hidden':
			case 'radio':
			case 'checkbox':
			case 'select':
		} // end switch
		
		// if necessary, check to make sure value matches pattern
		if ( ! empty( $this->attributes['pattern'] ) && ! preg_match( $this->attributes['pattern'], $match_value ) ) {
			return "{$label} is not in the correct format.";
		}
		if ( $type == 'color' ) {
			if ( ! empty( $color_type ) && ! empty( $_PATTERNS[$type][$color_type] ) && ! preg_match( "/^{$_PATTERNS[$type][$color_type]}$/", $match_value ) ) {
				return "{$label} is not a recognized color value.";
			}
		} else {
			if ( ( $strict || $type == 'email' ) && ! empty( $_PATTERNS[$type] ) && ! preg_match( "/^{$_PATTERNS[$type]}$/", $match_value ) ) {
				return "{$label} is not in the correct format." . ( isset( $human_readable ) ? " ({$human_readable})" : '' );
			}
		}
	
		// check to make sure values are usable and within range
		switch ( $type ) {
			case 'datetime':
			case 'datetime-local':
			case 'date':
			case 'time':
			case 'month':
			case 'week':
				// check to make sure value can be interpreted as a date/time
				$utc = strtotime( $this->value );
				if ( $utc === false || $utc == -1 ) return "{$label} is not in the correct format." . ( isset( $human_readable ) ? " ({$human_readable})" : '' );
				// check to make sure value is in range
				if ( isset( $this->attributes['min'] ) ) {
					$utc_min = strtotime( $this->attributes['min'] );
					if ( ( $utc_min === false || $utc_min == -1 ) && $utc < $utc_min ) {
						if ( $change && !empty( $date_format ) ) {
							$this->value = date( $date_format, $utc_min );
						} else {
							return "{$label} is below minimum allowed value of {$this->attributes['min']}.";
						}
					}
				}
				if ( isset( $this->attributes['max'] ) ) {
					$utc_max = strtotime( $this->attributes['max'] );
					if ( ( $utc_max === false || $utc_max == -1 ) && $utc > $utc_max ) {
						if ( $change && !empty( $date_format ) ) {
							$this->value = date( $date_format, $utc_max );
						} else {
							return "{$label} is above maximum allowed value of {$this->attributes['max']}.";
						}
					}
				}
				// I don't know how date/time steps can be handled simply
				break;
			
			case 'tel':
				// will return false if there is an error
				if ( ! ( $phone = $this->formatPhoneNumber( $this->value ) ) ) {
					return "{$label} does not seem to be a valid phone number.";
				}
				// formats the phone number consistently
				if ( $change ) $this->value = $phone;
				break;
				
			case 'number':
			case 'range':
				// check to make sure in range, numeric
				if ( !is_numeric( $this->value ) ) return "{$label} must be numeric.";
				// check this before minimum and maximum so that if value is changed, we make sure it's not outside the proper range
				if ( isset( $this->attributes['step'] ) && is_numeric( $this->attributes['step'] ) && ( $remainder = fmod( $this->value, $this->attributes['step'] ) ) != 0 ) {
					if ( $change ) {
						// set value to closest matching increment step
						$this->value = $this->value - $remainder + ( $remainder > ( $this->attributes['step'] / 2 ) ? $this->attributes['step'] : 0 );
					} else {
						return "{$label} isn't in a correct increment of {$this->attributes['step']}.";
					}
				}
				if ( isset( $this->attributes['min'] ) && is_numeric( $this->attributes['min'] ) && $this->value < $this->attributes['min'] ) {
					if ( $change ) {
						$this->value = $this->attributes['min'];
					} else {
						return "{$label} is below minimum allowed value of {$this->attributes['min']}.";
					}
				}
				if ( isset( $this->attributes['max'] ) && is_numeric( $this->attributes['max'] ) && $this->value > $this->attributes['max'] ) {
					if ( $change ) {
						$this->value = $this->attributes['max'];
					} else {
						return "{$label} is above maximum allowed value of {$this->attributes['max']}.";
					}
				}
				break;
			
			case 'file':
				if ( ! isset( $this->attributes['name'] ) ) break;
				$name = str_replace( '[]', '', $this->attributes['name'] );
				// the file field seems to usually be present even if no file has been submitted
				// therefore the following check probably isn't very useful, but we'll leave it here in case.
				if ( empty( $_FILES[$name] ) ) {
					if ( $required ) {
						return "{$label} is a required field.";
					}
					break;
				}
				// convert $_FILES into one usable array
				if ( ! is_array( $_FILES[$name]['name'] ) ) {
					$files = array( $_FILES[$name] );
				} else {
					foreach ( $_FILES[$name]['name'] as $i => $filename ) {
						foreach ( $_FILES[$name] as $key => $value ) {
							$files["{$name}[{$i}]"][$key] = $value[$i];
						}
					}
				}
				// check each file
				foreach ( $files as $key => $file ) {
					if ( $file['error'] == UPLOAD_ERR_NO_FILE ) {
						unset( $files[$key] );
						if ( $required ) {
							return "{$label} is a required field.";
						} else {
							continue;
						}
					} elseif ( $file['error'] != UPLOAD_ERR_OK ) {
						unset( $files[$key] );
						$msg = 'File upload error';
						if ( array_key_exists( $file['error'], $_FILE_UPLOAD_ERROR_CODES ) ) {
							$msg .= ': ' . $_FILE_UPLOAD_ERROR_CODES[$file['error']] . "\r\n";
						} else {
							$msg .= ".\r\n";
						}
						return $msg;
					} else {
						if ( $ext = strtolower( strrchr( $file['name'], '.' ) ) ) {
							array_walk_recursive( $this->allowed_file_extensions, 'FormField::strtolowerd' );
							array_walk_recursive( $this->disallowed_file_extensions, 'FormField::strtolowerd' );
							if ( in_array( $ext, $this->disallowed_file_extensions ) 
								|| ! empty( $this->allowed_file_extensions ) && ! in_array( $ext, $this->allowed_file_extensions ) ) {
								return "{$label} contains a file type which is not allowed.";
							}
						}
						//$this->value[] = $file; // place the array of file info into value
					}
				}
				$this->value = $files; // place the array of file info into value
				break;
		} // end switch
		
		return true;
	} // end function 
	
	/* the following methods are for database interaction */
	
	// prepares $value for insertion into database query
	public function makeSqlSafe( $value )
	{
		$CommonData = new CommonData();
		$_PATTERNS = $CommonData->getCommonRegexPatterns();
		
		if ( isset( $this->attributes['type'] ) && strtolower( $this->attributes['type'] ) == 'checkbox' && count( $this->options ) <= 1 ) {
			// in this case, the checkbox field is acting like a boolean input
			// the input will not be present if the box was not checked
			// in this case it indicates to us that the value should be 'false' rather than null
			// since MySQL has no true boolean type, we use 0 for false and 1 for true
			if ( is_null( $value ) ) return 0;
		}
		
		if ( is_null( $value ) ) return null;
		
		// arrays can't be stored in the database directly
		// json is a standard way in which they can be stored as strings and converted back to arrays again later as needed
		if ( is_array( $value ) ) {
			$value = json_encode( $value );
		}
		
		// date/time fields may need to be reformatted
		if ( isset( $this->columnInfo['Type'] ) && ! is_numeric( $value ) ) {
			$type = strtoupper( $this->columnInfo['Type'] );
			switch ( $type ) {
				case 'DATE':
					if ( ! isset( $date_format ) ) $date_format = 'Y-m-d';
				case 'DATETIME':
				case 'TIMESTAMP':
					if ( ! isset( $date_format ) ) $date_format = 'Y-m-d H:i:s';
				case 'TIME':
					if ( ! isset( $date_format ) ) $date_format = 'H:i:s';
					if ( empty( $value ) ) return null;
					// because times can get mixed up in conversion, only reformat if not in correct format already
					if ( ! preg_match( "/^{$_PATTERNS['sql'][$type]}$/", trim( $value ) ) ) {
						if ( strtotime( $value ) !== FALSE ) {
							$value = date( $date_format, strtotime( $value ) );
						} else {
							return null; // not valid date/time
						}
					}
					break;
				
				case 'BIT':
				case 'BOOL':
				case 'BOOLEAN':
				case 'TINYINT':
				case 'SMALLINT':
				case 'MEDIUMINT':
				case 'INT':
				case 'INTEGER':
				case 'BIGINT':
				case 'YEAR':
				case 'FLOAT':
				case 'DOUBLE':
				case 'DEC':
				case 'DECIMAL':
				case 'NUMERIC':
				case 'FIXED':
					// blank text and related form input types will return empty strings, 
					// we convert these to null because MySQL will convert blank entries for number and date fields into values
					if ( ! is_numeric( $value ) ) return null;
					if ( preg_match( "/^{$_PATTERNS['sql']['binary_notation']}$/", $value ) ) return $value;
					break;
			} // end switch
		}
		
		return ( ! empty( $this->mysqli ) ) ? $this->mysqli->real_escape_string( $value ) : $value;
	} // end function
	
	// checks the submitted data to make sure it's valid for inserting into the database
	// mysql will accept most invalid values so long as the query syntax is correct
	// mysql will change invalid values into a valid value
	// this function checks more for warnings of when a value is invalid as opposed to errors which prevent the query from executing
	// returns true if valid or a warning message if invalid
	public function validateSQLValue()
	{
		$CommonData = new CommonData();
		$_PATTERNS = $CommonData->getCommonRegexPatterns();
		if ( empty( $this->columnInfo['Type'] ) ) return true;
		$label = '<span class="label">' . $this->makeHtmlSafe( $this->label ) . '</span>';
		$type = strtoupper( $this->columnInfo['Type'] );
		$value = ( ! is_array( $this->value ) ) ? trim( $this->value ) : json_encode( $this->value );
		if ( $value == '' ) return true;
		$check_numeric = false;
		$check_date_time = false;
		$warnings = array();
		
		switch ( $type ) {
			// Integer fields
			case 'BIT':
				$check_numeric = true;
				$bits = ( ! empty( $this->columnInfo['Length'] ) ) ? $this->columnInfo['Length'] : 1;
				$max = pow( 2, $bits ) - 1;
				$min = 0;
				$decimal_places = 0;
				break;
			case 'BOOL':
			case 'BOOLEAN': // same as tinyint(1)
			case 'TINYINT':
				if ( ! isset( $max ) ) $max = 255;
			case 'SMALLINT':
				if ( ! isset( $max ) ) $max = 65535;
			case 'MEDIUMINT':
				if ( ! isset( $max ) ) $max = 16777215;
			case 'INT':
			case 'INTEGER':
				if ( ! isset( $max ) ) $max = 4294967295;
			case 'BIGINT':
				if ( ! isset( $max ) ) $max = 18446744073709551615;
				$min = 0;
				$check_numeric = true;
				// if signed
				if ( $this->columnInfo['Unsigned'] === FALSE ) {
					$max += $min = -ceil( $max / 2 );
				}
				$decimal_places = 0;
				break;
			
			// Decimal fields
			case 'FLOAT':
				$check_numeric = true;
				$min = -3.402823466E+38;
				$max = 3.402823466E+38;
				break;
			case 'DOUBLE':
				$check_numeric = true;
				$min = -1.7976931348623157E+308;
				$max = 1.7976931348623157E+308;
				break;
			case 'DEC':
			case 'DECIMAL':
			case 'NUMERIC':
			case 'FIXED':
				$check_numeric = true;
				$digits = explode( ',', $this->columnInfo['Length'] );
				$decimal_places = ( isset( $digits[1] ) ) ? $digits[1] : 0;
				$digits = $digits[0] - $decimal_places;
				$max = pow( 10, $digits ) - 1;
				$min = ( $this->columnInfo['Unsigned'] === FALSE ) ? 0 - $max : 0;
				break;
			
			// Date/Time fields
			// if numeric, format properly and then check for pattern match
			case 'DATE':
				//YYYY-MM-DD
				$check_date_time = true;
				if ( is_numeric( $value ) && strlen( $value ) == 8 ) {
					$year = substr( $value, 0, 4 );
					$month = substr( $value, 4, 2 );
					$day = substr( $value, 6, 2 );
					$value = "{$year}-{$month}-{$day}";
				}
				//if ( ! isset( $max ) ) $max = 99991231;
				break;
			case 'DATETIME':
				//if ( ! isset( $max ) ) $max = 99991231235959;
			case 'TIMESTAMP':
				//YYYY-MM-DD HH:MM:SS
				$check_date_time = true;
				if ( is_numeric( $value ) && strlen( $value ) == 14 ) {
					$year = substr( $value, 0, 4 );
					$month = substr( $value, 4, 2 );
					$day = substr( $value, 6, 2 );
					$hour = substr( $value, 8, 2 );
					$minute = substr( $value, 10, 2 );
					$second = substr( $value, 12, 2 );
					$value = "{$year}-{$month}-{$day} {$hour}:{$minute}:{$second}";
				}
				if ( $type == 'TIMESTAMP' ) {
					if ( ! isset( $max ) ) $max = 20380119031407;
					if ( ! isset( $min ) ) $min = 19700101000001;
				}
				break;
			case 'TIME':
				//HH:MM:SS
				$check_date_time = true;
				if ( is_numeric( $value ) ) {
					$value = str_pad( $value, 6, '0', STR_PAD_LEFT );
					$hour = substr( $value, 0, 2 );
					$minute = substr( $value, 2, 2 );
					$second = substr( $value, 4, 2 );
					$value = "{$hour}:{$minute}:{$second}";
				}
				//if ( ! isset( $max ) ) $max =  8385959;
				//if ( ! isset( $min ) ) $min = -8385959;
				break;
			case 'YEAR':
				//YYYY|YY
				$check_date_time = true;
				break;
			
			// Text fields
			case 'CHAR':
			case 'VARCHAR':
				$max_chars = $this->columnInfo['Length'];
				break;
			case 'TINYTEXT':
				$max_chars = 255;
				break;
			case 'TEXT':
				$max_chars = 65535;
				break;
			case 'MEDIUMTEXT':
			case 'LONG':
			case 'LONG VARCHAR':
				$max_chars = 16777215;
				break;
			case 'LONGTEXT':
				$max_chars = 4294967295;
				break;
			
			// Binary data fields
			case 'BINARY':
			case 'VARBINARY':
				$max_data_size = $this->columnInfo['Length']; // in bytes
				break;
			case 'TINYBLOB':
				$max_data_size = 255; // 255 bytes
			case 'BLOB':
				$max_data_size = 65535; // 64KiB
			case 'MEDIUMBLOB':
				$max_data_size = 16777215; // 16MiB
			case 'LONGBLOB':
				$max_data_size = 4294967295; // 4GiB
				break;
			
			// Set/Enum fields
			case 'ENUM':
				$options = explode( ',', $this->columnInfo['Length'] );
				array_walk_recursive( $options, 'FormField::strtolowerd' );
				if ( ! in_array( strtolower( $value ), $options ) ) {
					$value = $this->makeHtmlSafe( $value );
					$warnings[] = "Warning: {$value} is not a valid option for {$label}; this will result in an empty entry.";
				}
				break;
			case 'SET':
				$options = explode( ',', $this->columnInfo['Length'] );
				array_walk_recursive( $options, 'FormField::strtolowerd' );
				$values = explode( ',', $value );
				foreach ( $values as $v ) {
					if ( empty( $v ) ) continue;
					if ( ! in_array( strtolower( trim( $v ) ), $options ) ) {
						$v = $this->makeHtmlSafe( $v );
						$warnings[] = "Warning: {$v} is not a valid option for {$label}.";
					}
				}
				break;
			
		} // end switch
		
		// check date/time
		// is there a good way to check for invalid dates such as 02-31?
		if ( $check_date_time && ! preg_match( "/^{$_PATTERNS['sql'][$type]}$/", $value ) && strtotime( $value ) === FALSE ) {
			$warnings[] = "Warning: {$label} is either out of range or formatted improperly and will not be saved.";
		}
		
		// check to make sure values for numbers fields are numeric
		if ( $check_numeric ) {
			if ( preg_match( "/^{$_PATTERNS['sql']['binary_notation']}$/", $value ) ) {
				// change value to decimal in order to check min/max
				$value = bindec( str_replace( array( 'b', "'" ), '', $value ) );
			} elseif ( ! ( is_numeric( $value ) ) ) {

				$warnings[] = "Warning: {$label} is not numeric and will therefore be changed to 0.";
			}
		}
		
		// if limited in number of digits after decimal (precision), check to see if value is longer
		if ( isset( $decimal_places ) ) {
			$max = ( isset( $max ) ) ? $max : 0;
			$max += ( pow( 10, $decimal_places ) - 1 ) / pow( 10, $decimal_places );
			if ( $decimal_places ) $min = ( $this->columnInfo['Unsigned'] === FALSE ) ? 0 - $max : 0;
			// get length of decimal portion of value, if any
			if ( strpos( $value, '.' ) !== false && strlen( substr( $value, strpos( $value, '.' ) + 1 ) ) > $decimal_places ) {
				$warnings[] = "Warning: {$label} contains more than {$decimal_places} decimal places and will therefore be rounded off.";
			}
		}
		
		// check to make sure value is above minimum
		if ( isset( $min ) && is_numeric( $value ) && preg_replace ( '/[^0-9]/', '', $value ) < $min ) {
			$warnings[] = "Warning: {$label} is below minimum allowed value of {$min} and will therefore be changed to {$min}.";
		}
		// check to make sure value is less than maximum
		if ( isset( $max ) && is_numeric( $value ) && preg_replace ( '/[^0-9]/', '', $value ) > $max ) {
			$warnings[] = "Warning: {$label} is above maximum allowed value of {$max} and will therefore be changed to {$max}.";
		}
		
		// warn if text will be truncated
		if ( isset( $max_chars ) && strlen( $value ) > $max_chars ) {
			$warnings[] = "Warning: {$label} contains more than the maximum of {$max_chars} characters and will therefore be truncated.";
		}
				
		/*// warn if data too large
		// $max_data_size in bytes
		if ( isset( $max_data_size ) && $value > $max_data_size ) { // don't know how we check the size of $value
			$warnings[] = "Warning: {$label} is larger than the maximum of {$max_data_size} bytes and will therefore be truncated.";
		}*/
		
		return ( empty( $warnings ) ) ? true : $warnings;
	} // end function
	
} // end class

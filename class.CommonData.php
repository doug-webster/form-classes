<?php
class CommonData
{
	public function getCommonRegexPatterns( $delimiter = '/' )
	{
		// basic patterns
		$_PATTERNS['year']          = '([0-9]{4})';
		$_PATTERNS['month_no_year'] = '(0[1-9]|1[0-2])'; // 01-12
		$_PATTERNS['day']           = '(0[1-9]|[12][0-9]|3[01])'; // 01-31
		$_PATTERNS['hour']          = '([01][0-9]|2[0-3])'; // 00-23
		$_PATTERNS['minute']        = '([0-5][0-9])'; // 00-59
		$_PATTERNS['second']        = '([0-5][0-9]|60)'; // 00-60
		$_PATTERNS['0-255']         = '([0-9]{1,2}|[01][0-9]{2}|2[0-4][0-9]|25[0-5])';
		$_PATTERNS['0-838']         = '([0-9]{1,2}|[0-7][0-9]{2}|8[0-2][0-9]|83[0-8])';
		$_PATTERNS['1000-9999']     = '([1-9][0-9]{3})';
		$_PATTERNS['1970-2038']     = '(19[7-9][0-9]|20[0-2][0-9]|203[0-8])';
		$_PATTERNS['1901-2155']     = '(190[1-9]|19[1-9][0-9]|20[0-9]{2}|21[0-4][0-9]|215[0-5])';
		$_PATTERNS['week_of_year']  = '(0[1-9]|[1-4][0-9]|5[0-3])'; // 01-53
		$_PATTERNS['0-360']         = '([0-9]{1,2}|[0-2][0-9]{2}|3[0-5][0-9]|360)';
		$_PATTERNS['0-1.0']         = '(0(\.[0-9]+)?|1(\.0+)?)';
		$_PATTERNS['0-100%']        = '([0-9]{1,2}|100)%';
		
		// HTML5 inputs
		$_PATTERNS['date']           = "{$_PATTERNS['year']}-{$_PATTERNS['month_no_year']}-{$_PATTERNS['day']}";
		$_PATTERNS['time_no_ms']     = "{$_PATTERNS['hour']}:{$_PATTERNS['minute']}:{$_PATTERNS['second']}";
		$_PATTERNS['time']           = "{$_PATTERNS['time_no_ms']}(.[0-9]{1,2})?";
		$_PATTERNS['datetime']       = "{$_PATTERNS['date']}T{$_PATTERNS['time_no_ms']}(Z|[+-]{$_PATTERNS['hour']}:{$_PATTERNS['minute']})";
		$_PATTERNS['datetime-local'] = "{$_PATTERNS['date']}T{$_PATTERNS['time']}";
		$_PATTERNS['month']          = "{$_PATTERNS['year']}-{$_PATTERNS['month_no_year']}";
		$_PATTERNS['week']           = "{$_PATTERNS['year']}-W{$_PATTERNS['week_of_year']}";

		//$_PATTERNS['tel'] = ''; // it's easier to strip everything but the digits and then examine what's left
		// not perfect but should work for most cases; requires at least one '.'; '.' and '-' can't begin or end a sequence
		$_PATTERNS['domain'] = '([a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?';
		$_PATTERNS['email'] = "[a-zA-Z0-9!#$%&'*+/=?^_`{|}~-]+(\.[a-zA-Z0-9!#$%&'*+/=?^_`{|}~-]+)*@{$_PATTERNS['domain']}";
		//$_PATTERNS['url'] = ''; // there are too many theoretically possible values to have one regex for all cases
		
		// CSS3 color patterns
		$_PATTERNS['color']['hex']        = '#([0-9A-Fa-f]{6}|[0-9A-Fa-f]{3})';
		$_PATTERNS['color']['hex_strict'] = '#[0-9A-Fa-f]{6}'; // technically the only acceptable pattern for html color input, as I understand it
		$_PATTERNS['color']['rgb']        = "rgb\({$_PATTERNS['0-255']}, *{$_PATTERNS['0-255']}, *{$_PATTERNS['0-255']}\)";
		$_PATTERNS['color']['rgba']       = "rgba\({$_PATTERNS['0-255']}, *{$_PATTERNS['0-255']}, *{$_PATTERNS['0-255']}, *{$_PATTERNS['0-1.0']}\)";
		$_PATTERNS['color']['hsl']        = "hsl\({$_PATTERNS['0-360']}, *{$_PATTERNS['0-100%']}, *{$_PATTERNS['0-100%']}\)";
		$_PATTERNS['color']['hsla']       = "hsla\({$_PATTERNS['0-360']}, *{$_PATTERNS['0-100%']}, *{$_PATTERNS['0-100%']}, *{$_PATTERNS['0-1.0']}\)";
		
		// SQL date/time patterns
		$_PATTERNS['sql']['DATE']      = "{$_PATTERNS['1000-9999']}-{$_PATTERNS['month_no_year']}-{$_PATTERNS['day']}";
		$_PATTERNS['sql']['DATETIME']  = "{$_PATTERNS['sql']['DATE']} {$_PATTERNS['time_no_ms']}";
		$_PATTERNS['sql']['TIMESTAMP'] = "{$_PATTERNS['1970-2038']}-{$_PATTERNS['month_no_year']}-{$_PATTERNS['day']} {$_PATTERNS['time_no_ms']}";
		$_PATTERNS['sql']['TIME']      = "-?{$_PATTERNS['0-838']}:{$_PATTERNS['minute']}:{$_PATTERNS['second']}";
		$_PATTERNS['sql']['YEAR']      = "(([0-9]{1,2})|{$_PATTERNS['1901-2155']})";
		
		$_PATTERNS['sql']['binary_notation'] = "b'[0-1]+'";
		
		// other patterns
		$_PATTERNS['us_zip'] = '([0-9]{5}(-[0-9]{4})?)';
		$_PATTERNS['ca_postal_code'] = '([A-Za-z][0-9][A-Za-z] *[0-9][A-Za-z][0-9])';
		$_PATTERNS['social_security_number'] = '[0-9]{3}(-| )?[0-9]{2}(-| )?[0-9]{4}';
		$_PATTERNS['dollar_amount'] = '\$?[0-9,]+(\.[0-9]{2})?';
		$_PATTERNS['ascii_punctuation'] = '[!"#$%&\'()*+,-./:;<=>?@[\\\\\]^_`{|}~]';
		$_PATTERNS['ascii_printable'] = '[\x20-\x7E]';
		$_PATTERNS['ascii_printable_no_space'] = '[\x21-\x7E]';
		
		array_walk_recursive( $_PATTERNS, 'CommonData::escapeRegExPattern', $delimiter );
		
		return $_PATTERNS;
	} // end function
	
	public function escapeRegExPattern( &$pattern, $key, $delimiter )
	{
		$pattern = str_replace( $delimiter, "\\{$delimiter}", $pattern );
	}
	
	public function getCSSColorNames()
	{
		return array(
			'black' => '#000000',
			'navy' => '#000080',
			'darkblue' => '#00008B',
			'mediumblue' => '#0000CD',
			'blue' => '#0000FF',
			'darkgreen' => '#006400',
			'green' => '#008000',
			'teal' => '#008080',
			'darkcyan' => '#008B8B',
			'deepskyblue' => '#00BFFF',
			'darkturquoise' => '#00CED1',
			'mediumspringgreen' => '#00FA9A',
			'lime' => '#00FF00',
			'springgreen' => '#00FF7F',
			'aqua' => '#00FFFF',
			'cyan' => '#00FFFF',
			'midnightblue' => '#191970',
			'dodgerblue' => '#1E90FF',
			'lightseagreen' => '#20B2AA',
			'forestgreen' => '#228B22',
			'seagreen' => '#2E8B57',
			'darkslategray' => '#2F4F4F',
			'limegreen' => '#32CD32',
			'mediumseagreen' => '#3CB371',
			'turquoise' => '#40E0D0',
			'royalblue' => '#4169E1',
			'steelblue' => '#4682B4',
			'darkslateblue' => '#483D8B',
			'mediumturquoise' => '#48D1CC',
			'indigo' => '#4B0082',
			'darkolivegreen' => '#556B2F',
			'cadetblue' => '#5F9EA0',
			'cornflowerblue' => '#6495ED',
			'mediumaquamarine' => '#66CDAA',
			'dimgray' => '#696969',
			'slateblue' => '#6A5ACD',
			'olivedrab' => '#6B8E23',
			'slategray' => '#708090',
			'lightslategray' => '#778899',
			'mediumslateblue' => '#7B68EE',
			'lawngreen' => '#7CFC00',
			'chartreuse' => '#7FFF00',
			'aquamarine' => '#7FFFD4',
			'maroon' => '#800000',
			'purple' => '#800080',
			'olive' => '#808000',
			'gray' => '#808080',
			'skyblue' => '#87CEEB',
			'lightskyblue' => '#87CEFA',
			'blueviolet' => '#8A2BE2',
			'darkred' => '#8B0000',
			'darkmagenta' => '#8B008B',
			'saddlebrown' => '#8B4513',
			'darkseagreen' => '#8FBC8F',
			'lightgreen' => '#90EE90',
			'mediumpurple' => '#9370DB',
			'darkviolet' => '#9400D3',
			'palegreen' => '#98FB98',
			'darkorchid' => '#9932CC',
			'yellowgreen' => '#9ACD32',
			'sienna' => '#A0522D',
			'brown' => '#A52A2A',
			'darkgray' => '#A9A9A9',
			'lightblue' => '#ADD8E6',
			'greenyellow' => '#ADFF2F',
			'paleturquoise' => '#AFEEEE',
			'lightsteelblue' => '#B0C4DE',
			'powderblue' => '#B0E0E6',
			'firebrick' => '#B22222',
			'darkgoldenrod' => '#B8860B',
			'mediumorchid' => '#BA55D3',
			'rosybrown' => '#BC8F8F',
			'darkkhaki' => '#BDB76B',
			'silver' => '#C0C0C0',
			'mediumvioletred' => '#C71585',
			'indianred' => '#CD5C5C',
			'peru' => '#CD853F',
			'chocolate' => '#D2691E',
			'tan' => '#D2B48C',
			'lightgray' => '#D3D3D3',
			'thistle' => '#D8BFD8',
			'orchid' => '#DA70D6',
			'goldenrod' => '#DAA520',
			'palevioletred' => '#DB7093',
			'crimson' => '#DC143C',
			'gainsboro' => '#DCDCDC',
			'plum' => '#DDA0DD',
			'burlywood' => '#DEB887',
			'lightcyan' => '#E0FFFF',
			'lavender' => '#E6E6FA',
			'darksalmon' => '#E9967A',
			'violet' => '#EE82EE',
			'palegoldenrod' => '#EEE8AA',
			'lightcoral' => '#F08080',
			'khaki' => '#F0E68C',
			'aliceblue' => '#F0F8FF',
			'honeydew' => '#F0FFF0',
			'azure' => '#F0FFFF',
			'sandybrown' => '#F4A460',
			'wheat' => '#F5DEB3',
			'beige' => '#F5F5DC',
			'whitesmoke' => '#F5F5F5',
			'mintcream' => '#F5FFFA',
			'ghostwhite' => '#F8F8FF',
			'salmon' => '#FA8072',
			'antiquewhite' => '#FAEBD7',
			'linen' => '#FAF0E6',
			'lightgoldenrodyellow' => '#FAFAD2',
			'oldlace' => '#FDF5E6',
			'red' => '#FF0000',
			'fuchsia' => '#FF00FF',
			'magenta' => '#FF00FF',
			'deeppink' => '#FF1493',
			'orangered' => '#FF4500',
			'tomato' => '#FF6347',
			'hotpink' => '#FF69B4',
			'coral' => '#FF7F50',
			'darkorange' => '#FF8C00',
			'lightsalmon' => '#FFA07A',
			'orange' => '#FFA500',
			'lightpink' => '#FFB6C1',
			'pink' => '#FFC0CB',
			'gold' => '#FFD700',
			'peachpuff' => '#FFDAB9',
			'navajowhite' => '#FFDEAD',
			'moccasin' => '#FFE4B5',
			'bisque' => '#FFE4C4',
			'mistyrose' => '#FFE4E1',
			'blanchedalmond' => '#FFEBCD',
			'papayawhip' => '#FFEFD5',
			'lavenderblush' => '#FFF0F5',
			'seashell' => '#FFF5EE',
			'cornsilk' => '#FFF8DC',
			'lemonchiffon' => '#FFFACD',
			'floralwhite' => '#FFFAF0',
			'snow' => '#FFFAFA',
			'yellow' => '#FFFF00',
			'lightyellow' => '#FFFFE0',
			'ivory' => '#FFFFF0',
			'white' => '#FFFFFF',
		);
	} // end function
	
	public function getFileUploadErrorCodes()
	{
		return array(
			UPLOAD_ERR_OK => 'There is no error, the file uploaded with success.',
			UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive.',
			UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
			UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
			UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
			UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
			UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
			UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help.',
		);
	} // end function

	public function getStates( $extended = false )
	{
		$states = array(
			'AL' => 'Alabama',
			'AK' => 'Alaska',
			'AZ' => 'Arizona',
			'AR' => 'Arkansas',
			'CA' => 'California',
			'CO' => 'Colorado',
			'CT' => 'Connecticut',
			'DE' => 'Delaware',
			'DC' => 'Washington D.C.',
			'FL' => 'Florida',
			'GA' => 'Georgia',
			'HI' => 'Hawaii',
			'ID' => 'Idaho',
			'IL' => 'Illinois',
			'IN' => 'Indiana',
			'IA' => 'Iowa',
			'KS' => 'Kansas',
			'KY' => 'Kentucky',
			'LA' => 'Louisiana',
			'ME' => 'Maine',
			'MD' => 'Maryland',
			'MA' => 'Massachusetts',
			'MI' => 'Michigan',
			'MN' => 'Minnesota',
			'MS' => 'Mississippi',
			'MO' => 'Missouri',
			'MT' => 'Montana',
			'NE' => 'Nebraska',
			'NV' => 'Nevada',
			'NH' => 'New Hampshire',
			'NJ' => 'New Jersey',
			'NM' => 'New Mexico',
			'NY' => 'New York',
			'NC' => 'North Carolina',
			'ND' => 'North Dakota',
			'OH' => 'Ohio',
			'OK' => 'Oklahoma',
			'OR' => 'Oregon',
			'PA' => 'Pennsylvania',
			'RI' => 'Rhode Island',
			'SC' => 'South Carolina',
			'SD' => 'South Dakota',
			'TN' => 'Tennessee',
			'TX' => 'Texas',
			'UT' => 'Utah',
			'VT' => 'Vermont',
			'VA' => 'Virginia',
			'WA' => 'Washington',
			'WV' => 'West Virginia',
			'WI' => 'Wisconsin',
			'WY' => 'Wyoming',
		);
		$us_territories = array(
			'AS' => 'American Samoa',
			'FM' => 'Federated States of Micronesia',
			'GU' => 'Guam',
			'MH' => 'Marshall Islands',
			'MP' => 'Northern Mariana Islands',
			'PR' => 'Puerto Rico',
			'PW' => 'Palau',
			'VI' => 'U.S. Virgin Islands',
		);
		
		return ( $extended ) ? array_merge( $states, $us_territories ) : $states;
	} // end function

	public function getCanadianProvinces()
	{
		return array(
			'AB' => 'Alberta', 
			'BC' => 'British Columbia', 
			'MB' => 'Manitoba', 
			'NB' => 'New Brunswick', 
			'NL' => 'Newfoundland and Labrador', 
			'NT' => 'Northwest Territories', 
			'NS' => 'Nova Scotia', 
			'NU' => 'Nunavut', 
			'ON' => 'Ontario', 
			'PE' => 'Prince Edward Island', 		
			'QC' => 'Quebec', 
			'SK' => 'Saskatchewan', 
			'YT' => 'Yukon' 
		);
	} // end function

	public function getCountries()
	{
		// I don't now recall where I got this list of countries from
		// there are ~500 on this list and ~250 on the ISO 3166-1 list below
		return array(
			'United States', 
			'Canada', 
			'Abu Dhabi (United Arab Emirates)', 
			'Admiralty Islands (Papua New Guinea)', 
			'Afghanistan', 
			'Aitutaki, Cook Islands (New Zealand)', 
			'Ajman (United Arab Emirates)', 
			'Åland Island (Finland)', 
			'Albania', 
			'Alberta (Canada)', 
			'Alderney (Channel Islands) (Great Britain and Northern Ireland)', 
			'Algeria', 
			'Alhucemas (Spain)', 
			'Alofi Island (New Caledonia)', 
			'American Samoa, United States', 
			'Andaman Islands (India)', 
			'Andorra', 
			'Angola', 
			'Anguilla', 
			'Anjouan (Comoros)', 
			'Annobon Island (Equatorial Guinea)', 
			'Antigua (Antigua and Barbuda)', 
			'Antigua and Barbuda', 
			'Argentina', 
			'Armenia', 
			'Aruba', 
			'Ascension', 
			'Astypalaia (Greece)', 
			'Atafu (Western Samoa)', 
			'Atiu, Cook Islands (New Zealand)', 
			'Australia', 
			'Austria', 
			'Avarua (New Zealand)', 
			'Azerbaijan', 
			'Azores (Portugal)', 
			'Bahamas', 
			'Bahrain', 
			'Balearic Islands (Spain)', 
			'Baluchistan (Pakistan)', 
			'Bangladesh', 
			'Banks Island (Vanuatu)', 
			'Barbados', 
			'Barbuda (Antigua and Barbuda)', 
			'Barthelemy (Guadeloupe)', 
			'Belarus', 
			'Belgium', 
			'Belize', 
			'Benin', 
			'Bermuda', 
			'Bhutan', 
			'Bismark Archipelago (Papua New Guinea)', 
			'Bolivia', 
			'Bonaire (Netherlands Antilles)', 
			'Borabora (French Polynesia)', 
			'Borneo (Indonesia)', 
			'Bosnia-Herzegovina', 
			'Botswana', 
			'Bougainville (Papua New Guinea)', 
			'Bourbon (Reunion)', 
			'Brazil', 
			'British Columbia (Canada)', 
			'British Guiana (Guyana)', 
			'British Honduras (Belize)', 
			'British Virgin Islands', 
			'Brunei Darussalam', 
			'Buka (Papua New Guinea)', 
			'Bulgaria', 
			'Burkina Faso', 
			'Burma', 
			'Burundi', 
			'Caicos Islands (Turks and Caicos Islands)', 
			'Cambodia', 
			'Cameroon', 
			'Canary Islands (Spain)', 
			'Canton Island (Kiribati)', 
			'Cape Verde', 
			'Cayman Islands', 
			'Central African Republic', 
			'Ceuta (Spain)', 
			'Ceylon (Sri Lanka)', 
			'Chad', 
			'Chaferinas Islands (Spain)', 
			'Chalki (Greece)', 
			'Channel Islands (Jersey, Guernsey, Alderney, and Sark) (Great Britain and Northern Ireland)', 
			'Chile', 
			'China', 
			'Christiansted, US Virgin Islands, United States', 
			'Christmas Island (Australia)', 
			'Christmas Island (Kiribati)', 
			'Chuuk, Micronesia, United States', 
			'Cocos Island (Australia)', 
			'Colombia', 
			'Comoros', 
			'Congo, Democratic Republic of the', 
			'Congo, Republic of the', 
			'Cook Islands (New Zealand)', 
			'Corisco Island (Equatorial Guinea)', 
			'Corsica (France)', 
			'Costa Rica', 
			'Cote d’Ivoire', 
			'Crete (Greece)', 
			'Croatia', 
			'Cuba', 
			'Cumino Island (Malta)', 
			'Curacao (Netherlands Antilles)', 
			'Cyjrenaica (Libya)', 
			'Cyprus', 
			'Czech Republic', 
			'Dahomey (Benin)', 
			'Damao (India)', 
			'Danger Islands (New Zealand)', 
			'Denmark', 
			'Desirade Island (Guadeloupe)', 
			'Diu (India)', 
			'Djibouti', 
			'Dodecanese Islands (Greece)', 
			'Doha (Qatar)', 
			'Dominica', 
			'Dominican Republic', 
			'Dubai (United Arab Emirates)', 
			'East Timor (Indonesia)', 
			'Ebeye, Marshall Islands, United States', 
			'Ecuador', 
			'Egypt', 
			'Eire (Ireland)', 
			'El Salvador', 
			'Ellice Islands (Tuvalu)', 
			'Elobey Islands (Equatorial Guinea)', 
			'Enderbury Island (Kiribati)', 
			'England (Great Britain and Northern Ireland)', 
			'Equatorial Guinea', 
			'Eritrea', 
			'Estonia', 
			'Ethiopia', 
			'Fakaofo (Western Samoa)', 
			'Falkland Islands', 
			'Fanning Island (Kiribati)', 
			'Faroe Islands', 
			'Fernando Po (Equatorial Guinea)', 
			'Fezzan (Libya)', 
			'Fiji', 
			'Finland', 
			'Formosa (Taiwan)', 
			'France', 
			'Frederiksted, US Virgin Islands, United States', 
			'French Guiana', 
			'French Oceania (French Polynesia)', 
			'French Polynesia', 
			'French Somaliland (Djibouti)', 
			'French Territory of the Afars and Issas (Djibouti)', 
			'French West Indies (Guadeloupe)', 
			'French West Indies (Martinique)', 
			'Friendly Islands (Tonga)', 
			'Fujairah (United Arab Emirates)', 
			'Futuna (Wallis and Futuna Islands)', 
			'Gabon', 
			'Gambia', 
			'Gambier (French Polynesia)', 
			'Georgia, Republic of', 
			'Germany', 
			'Ghana', 
			'Gibraltar', 
			'Gilbert Islands (Kiribati)', 
			'Goa (India)', 
			'Gozo Island (Malta)', 
			'Grand Comoro (Comoros)', 
			'Great Britain and Northern Ireland', 
			'Greece', 
			'Greenland', 
			'Grenada', 
			'Grenadines (Saint Vincent and the Grenadines)', 
			'Guadeloupe', 
			'Guam, United States', 
			'Guatemala', 
			'Guernsey (Channel Islands) (Great Britain and Northern Ireland)', 
			'Guinea', 
			'Guinea–Bissau', 
			'Guyana', 
			'Hainan Island (China)', 
			'Haiti', 
			'Hashemite Kingdom (Jordan)', 
			'Hervey, Cook Islands (New Zealand)', 
			'Hivaoa (French Polynesia)', 
			'Holland (Netherlands)', 
			'Honduras', 
			'Hong Kong', 
			'Huahine (French Polynesia)', 
			'Huan Island (New Caledonia)', 
			'Hungary', 
			'Iceland', 
			'India', 
			'Indonesia', 
			'Iran', 
			'Iraq', 
			'Ireland', 
			'Irian Barat (Indonesia)', 
			'Isle of Man (Great Britain and Northern Ireland)', 
			'Isle of Pines (New Caledonia)', 
			'Isle of Pines, West Indies (Cuba)', 
			'Israel', 
			'Issas (Djibouti)', 
			'Italy', 
			'Ivory Coast (Cote d’Ivoire)', 
			'Jamaica', 
			'Japan', 
			'Jersey (Channel Islands) (Great Britain and Northern Ireland)', 
			'Johore (Malaysia)', 
			'Jordan', 
			'Kalymnos (Greece)', 
			'Kampuchea (Cambodia)', 
			'Karpathos (Greece)', 
			'Kassos (Greece)', 
			'Kastellorizon (Greece)', 
			'Kazakhstan', 
			'Kedah (Malaysia)', 
			'Keeling Islands (Australia)', 
			'Kelantan (Malaysia)', 
			'Kenya', 
			'Kingshill, US Virgin Islands, United States', 
			'Kiribati', 
			'Korea, Democratic People’s Republic of (North Korea)', 
			'Korea, Republic of (South Korea)', 
			'Koror (Palau), United States', 
			'Kos (Greece)', 
			'Kosovo, Republic of', 
			'Kosrae, Micronesia, United States', 
			'Kowloon (Hong Kong)', 
			'Kuwait', 
			'Kwajalein, Marshall Islands, United States', 
			'Kyrgyzstan', 
			'Labrador (Canada)', 
			'Labuan (Malaysia)', 
			'Laos', 
			'Latvia', 
			'Lebanon', 
			'Leipsos (Greece)', 
			'Leros (Greece)', 
			'Les Saints Island (Guadeloupe)', 
			'Lesotho', 
			'Liberia', 
			'Libya', 
			'Liechtenstein', 
			'Lithuania', 
			'Lord Howe Island (Australia)', 
			'Loyalty Islands (New Caledonia)', 
			'Luxembourg', 
			'Macao', 
			'Macau (Macao)', 
			'Macedonia, Republic of', 
			'Madagascar', 
			'Madeira Islands (Portugal)', 
			'Majuro, Marshall Islands, United States', 
			'Malacca (Malaysia)', 
			'Malagasy Republic (Madagascar)', 
			'Malawi', 
			'Malaya (Malaysia)', 
			'Malaysia', 
			'Maldives', 
			'Mali', 
			'Malta', 
			'Manahiki (New Zealand)', 
			'Manchuria (China)', 
			'Manitoba (Canada)', 
			'Manua Islands, American Samoa, United States', 
			'Marie Galante (Guadeloupe)', 
			'Marquesas Islands (French Polynesia)', 
			'Marshall Islands, Republic of the, United States', 
			'Martinique', 
			'Mauritania', 
			'Mauritius', 
			'Mayotte (France)', 
			'Melilla (Spain)', 
			'Mexico', 
			'Micronesia, Federated States of, United States', 
			'Miquelon (Saint Pierre and Miquelon)', 
			'Moheli (Comoros)', 
			'Moldova', 
			'Monaco (France)', 
			'Mongolia', 
			'Montenegro', 
			'Montserrat', 
			'Moorea (French Polynesia)', 
			'Morocco', 
			'Mozambique', 
			'Muscat (Oman)', 
			'Myanmar (Burma)', 
			'Namibia', 
			'Nansil Islands (Japan)', 
			'Nauru', 
			'Negri Sembilan (Malaysia)', 
			'Nepal', 
			'Netherlands', 
			'Netherlands Antilles', 
			'Netherlands West Indies (Netherlands Antilles)', 
			'Nevis (Saint Christopher and Nevis)', 
			'New Britain (Papua New Guinea)', 
			'New Brunswick (Canada)', 
			'New Caledonia', 
			'New Hanover (Papua New Guinea)', 
			'New Hebrides (Vanuatu)', 
			'New Ireland (Papua New Guinea)', 
			'New South Wales (Australia)', 
			'New Zealand', 
			'Newfoundland (Canada)', 
			'Nicaragua', 
			'Niger', 
			'Nigeria', 
			'Nissiros (Greece)', 
			'Niue (New Zealand)', 
			'Norfolk Island (Australia)', 
			'North Borneo (Malaysia)', 
			'North Korea (Korea, Democratic People’s Republic of)', 
			'Northern Ireland (Great Britain and Northern Ireland)', 
			'Northern Mariana Islands, Commonwealth of, United States', 
			'Northwest Territory (Canada)', 
			'Norway', 
			'Nova Scotia (Canada)', 
			'Nukahiva (French Polynesia)', 
			'Nukunonu (Western Samoa)', 
			'Nyasaland (Malawi)', 
			'Ocean Island (Kiribati)', 
			'Okinawa (Japan)', 
			'Oman', 
			'Ontario (Canada)', 
			'Pago Pago, American Samoa, United States', 
			'Pahang (Malaysia)', 
			'Pakistan', 
			'Palau, United States', 
			'Palmerston, Avarua (New Zealand)', 
			'Panama', 
			'Papua New Guinea', 
			'Paraguay', 
			'Parry, Cook Islands (New Zealand)', 
			'Patmos (Greece)', 
			'Pemba (Tanzania)', 
			'Penang (Malaysia)', 
			'Penghu Islands (Taiwan)', 
			'Penon de Velez de la Gomera (Spain)', 
			'Penrhyn, Tongareva (New Zealand)', 
			'Perak (Malaysia)', 
			'Perlis (Malaysia)', 
			'Persia (Iran)', 
			'Peru', 
			'Pescadores Islands (Taiwan)', 
			'Petite Terre (Guadeloupe)', 
			'Philippines', 
			'Pitcairn Island', 
			'Pohnpei, Micronesia, United States', 
			'Poland', 
			'Portugal', 
			'Prince Edward Island (Canada)', 
			'Province Wellesley (Malaysia)', 
			'Puerto Rico, United States', 
			'Pukapuka (New Zealand)', 
			'Qatar', 
			'Quebec (Canada)', 
			'Queensland (Australia)', 
			'Quemoy (Taiwan)', 
			'Raiatea (French Polynesia)', 
			'Rakaanga (New Zealand)', 
			'Rapa (French Polynesia)', 
			'Rarotonga, Cook Islands (New Zealand)', 
			'Ras al Kaimah (United Arab Emirates)', 
			'Redonda (Antigua and Barbuda)', 
			'Reunion', 
			'Rhodesia (Zimbabwe)', 
			'Rio Muni (Equatorial Guinea)', 
			'Rodos (Greece)', 
			'Rodrigues (Mauritius)', 
			'Romania', 
			'Rota, Northern Mariana Islands, United States', 
			'Russia', 
			'Rwanda', 
			'Saba (Netherlands Antilles)', 
			'Sabah (Malaysia)', 
			'Saint Barthelemy (Guadeloupe)', 
			'Saint Bartholomew (Guadeloupe)', 
			'Saint Christopher and Nevis', 
			'Saint Croix, US Virgin Islands, United States', 
			'Saint Eustatius (Netherlands Antilles)', 
			'Saint Helena', 
			'Saint John, US Virgin Islands, United States', 
			'Saint Kitts (Saint Christopher and Nevis)', 
			'Saint Lucia', 
			'Saint Maarten (Dutch) (Netherlands Antilles)', 
			'Saint Martin (French) (Guadeloupe)', 
			'Saint Pierre and Miquelon', 
			'Saint Thomas, US Virgin Islands, United States', 
			'Saint Vincent and the Grenadines', 
			'Sainte Marie de Madagascar (Madagascar)', 
			'Saipan, Northern Mariana Islands, United States', 
			'Salvador (El Salvador)', 
			'Samoa, American, United States', 
			'San Marino', 
			'Santa Cruz Islands (Solomon Island)', 
			'Sao Tome and Principe', 
			'Sarawak (Malaysia)', 
			'Sark (Channel Islands) (Great Britain and Northern Ireland)', 
			'Saskatchewan (Canada)', 
			'Saudi Arabia', 
			'Savage Island, Niue (New Zealand)', 
			'Savaii Island (Western Samoa)', 
			'Scotland (Great Britain and Northern Ireland)', 
			'Selangor (Malaysia)', 
			'Senegal', 
			'Serbia, Republic of', 
			'Seychelles', 
			'Sharja (United Arab Emirates)', 
			'Shikoku (Japan)', 
			'Siam (Thailand)', 
			'Sierra Leone', 
			'Sikkim (India)', 
			'Singapore', 
			'Slovak Republic (Slovakia)', 
			'Slovenia', 
			'Society Islands (French Polynesia)', 
			'Solomon Islands', 
			'Somali Democratic Republic (Somalia)', 
			'Somalia', 
			'Somaliland (Somalia)', 
			'South Africa', 
			'South Australia (Australia)', 
			'South Georgia (Falkland Islands)', 
			'South Korea (Korea, Republic of)', 
			'South–West Africa (Namibia)', 
			'Spain', 
			'Spitzbergen (Norway)', 
			'Sri Lanka', 
			'Sudan', 
			'Suriname', 
			'Suwarrow Islands (New Zealand)', 
			"Swain's Island, American Samoa, United States", 
			'Swan Islands (Honduras)', 
			'Swaziland', 
			'Sweden', 
			'Switzerland', 
			'Symi (Greece)', 
			'Syrian Arab Republic (Syria)', 
			'Tahaa (French Polynesia)', 
			'Tahiti (French Polynesia)', 
			'Taiwan', 
			'Tajikistan', 
			'Tanzania', 
			'Tasmania (Australia)', 
			'Tchad (Chad)', 
			'Thailand', 
			'Thursday Island (Australia)', 
			'Tibet (China)', 
			'Tilos (Greece)', 
			'Timor (Indonesia)', 
			'Tinian, Northern Mariana Islands, United States', 
			'Tobago (Trinidad and Tobago)', 
			'Togo', 
			'Tokelau (Union Group) (Western Samoa)', 
			'Tonga', 
			'Tongareva (New Zealand)', 
			'Tori Shima (Japan)', 
			'Torres Island (Vanuatu)', 
			'Trans-Jordan, Hashemite Kingdom (Jordan)', 
			'Transkei (South Africa)', 
			'Trengganu (Malaysia)', 
			'Trinidad and Tobago', 
			'Tripolitania (Libya)', 
			'Tristan da Cunha', 
			'Trucial States (United Arab Emirates)', 
			'Tuamotou (French Polynesia)', 
			'Tubuai (French Polynesia)', 
			'Tunisia', 
			'Turkey', 
			'Turkmenistan', 
			'Turks and Caicos Islands', 
			'Tutuila Island, American Samoa, United States', 
			'Tuvalu', 
			'Uganda', 
			'Ukraine', 
			'Umm al Quaiwain (United Arab Emirates)', 
			'Umm Said (Qatar)', 
			'Union Group (Western Samoa)', 
			'United Arab Emirates', 
			'United Kingdom (Great Britain and Northern Ireland)', 
			'United Nations, New York, United States', 
			'Upolu Island (Western Samoa)', 
			'Uruguay', 
			'Uzbekistan', 
			'Vanuatu', 
			'Vatican City', 
			'Venezuela', 
			'Victoria (Australia)', 
			'Vietnam', 
			'Virgin Islands (British)', 
			'Virgin Islands (US), United States', 
			'Wales (Great Britain and Northern Ireland)', 
			'Wallis and Futuna Islands', 
			'Wellesley, Province (Malaysia)', 
			'West New Guinea (Indonesia)', 
			'Western Australia (Australia)', 
			'Western Samoa', 
			'Yap, Micronesia, United States', 
			'Yemen', 
			'Yukon Territory (Canada)', 
			'Zafarani Islands (Spain)', 
			'Zambia', 
			'Zanzibar (Tanzania)', 
			'Zimbabwe'
		);
	} // end function
	
	// returns an array of ISO 3166-1 alpha-2 codes : country names
	// or the country name for the specified code
	public function getISOCountries( $code = false )
	{
		$data = array(
			'AF' => 'Afghanistan',
			'AX' => 'Åland Islands',
			'AL' => 'Albania',
			'DZ' => 'Algeria',
			'AS' => 'American Samoa',
			'AD' => 'Andorra',
			'AO' => 'Angola',
			'AI' => 'Anguilla',
			'AQ' => 'Antarctica',
			'AG' => 'Antigua and Barbuda',
			'AR' => 'Argentina',
			'AM' => 'Armenia',
			'AW' => 'Aruba',
			'AU' => 'Australia',
			'AT' => 'Austria',
			'AZ' => 'Azerbaijan',
			'BS' => 'Bahamas',
			'BH' => 'Bahrain',
			'BD' => 'Bangladesh',
			'BB' => 'Barbados',
			'BY' => 'Belarus',
			'BE' => 'Belgium',
			'BZ' => 'Belize',
			'BJ' => 'Benin',
			'BM' => 'Bermuda',
			'BT' => 'Bhutan',
			'BO' => 'Bolivia, Plurinational State of',
			'BQ' => 'Bonaire, Sint Eustatius and Saba',
			'BA' => 'Bosnia and Herzegovina',
			'BW' => 'Botswana',
			'BV' => 'Bouvet Island',
			'BR' => 'Brazil',
			'IO' => 'British Indian Ocean Territory',
			'BN' => 'Brunei Darussalam',
			'BG' => 'Bulgaria',
			'BF' => 'Burkina Faso',
			'BI' => 'Burundi',
			'KH' => 'Cambodia',
			'CM' => 'Cameroon',
			'CA' => 'Canada',
			'CV' => 'Cape Verde',
			'KY' => 'Cayman Islands',
			'CF' => 'Central African Republic',
			'TD' => 'Chad',
			'CL' => 'Chile',
			'CN' => 'China',
			'CX' => 'Christmas Island',
			'CC' => 'Cocos (Keeling) Islands',
			'CO' => 'Colombia',
			'KM' => 'Comoros',
			'CG' => 'Congo',
			'CD' => 'Congo, The Democratic Republic of the',
			'CK' => 'Cook Islands',
			'CR' => 'Costa Rica',
			'CI' => 'Côte d\'Ivoire',
			'HR' => 'Croatia',
			'CU' => 'Cuba',
			'CW' => 'CuraÇao',
			'CY' => 'Cyprus',
			'CZ' => 'Czech Republic',
			'DK' => 'Denmark',
			'DJ' => 'Djibouti',
			'DM' => 'Dominica',
			'DO' => 'Dominican Republic',
			'EC' => 'Ecuador',
			'EG' => 'Egypt',
			'SV' => 'El Salvador',
			'GQ' => 'Equatorial Guinea',
			'ER' => 'Eritrea',
			'EE' => 'Estonia',
			'ET' => 'Ethiopia',
			'FK' => 'Falkland Islands (Malvinas)',
			'FO' => 'Faroe Islands',
			'FJ' => 'Fiji',
			'FI' => 'Finland',
			'FR' => 'France',
			'GF' => 'French Guiana',
			'PF' => 'French Polynesia',
			'TF' => 'French Southern Territories',
			'GA' => 'Gabon',
			'GM' => 'Gambia',
			'GE' => 'Georgia',
			'DE' => 'Germany',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GR' => 'Greece',
			'GL' => 'Greenland',
			'GD' => 'Grenada',
			'GP' => 'Guadeloupe',
			'GU' => 'Guam',
			'GT' => 'Guatemala',
			'GG' => 'Guernsey',
			'GN' => 'Guinea',
			'GW' => 'Guinea-Bissau',
			'GY' => 'Guyana',
			'HT' => 'Haiti',
			'HM' => 'Heard Island and Mcdonald Islands',
			'VA' => 'Holy See (Vatican City State)',
			'HN' => 'Honduras',
			'HK' => 'Hong Kong',
			'HU' => 'Hungary',
			'IS' => 'Iceland',
			'IN' => 'India',
			'ID' => 'Indonesia',
			'IR' => 'Iran, Islamic Republic of',
			'IQ' => 'Iraq',
			'IE' => 'Ireland',
			'IM' => 'Isle of Man',
			'IL' => 'Israel',
			'IT' => 'Italy',
			'JM' => 'Jamaica',
			'JP' => 'Japan',
			'JE' => 'Jersey',
			'JO' => 'Jordan',
			'KZ' => 'Kazakhstan',
			'KE' => 'Kenya',
			'KI' => 'Kiribati',
			'KP' => 'Korea, Democratic People\'s Republic of',
			'KR' => 'Korea, Republic of',
			'KW' => 'Kuwait',
			'KG' => 'Kyrgyzstan',
			'LA' => 'Lao People\'s Democratic Republic',
			'LV' => 'Latvia',
			'LB' => 'Lebanon',
			'LS' => 'Lesotho',
			'LR' => 'Liberia',
			'LY' => 'Libya',
			'LI' => 'Liechtenstein',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'MO' => 'Macao',
			'MK' => 'Macedonia, The Former Yugoslav Republic of',
			'MG' => 'Madagascar',
			'MW' => 'Malawi',
			'MY' => 'Malaysia',
			'MV' => 'Maldives',
			'ML' => 'Mali',
			'MT' => 'Malta',
			'MH' => 'Marshall Islands',
			'MQ' => 'Martinique',
			'MR' => 'Mauritania',
			'MU' => 'Mauritius',
			'YT' => 'Mayotte',
			'MX' => 'Mexico',
			'FM' => 'Micronesia, Federated States of',
			'MD' => 'Moldova, Republic of',
			'MC' => 'Monaco',
			'MN' => 'Mongolia',
			'ME' => 'Montenegro',
			'MS' => 'Montserrat',
			'MA' => 'Morocco',
			'MZ' => 'Mozambique',
			'MM' => 'Myanmar',
			'NA' => 'Namibia',
			'NR' => 'Nauru',
			'NP' => 'Nepal',
			'NL' => 'Netherlands',
			'NC' => 'New Caledonia',
			'NZ' => 'New Zealand',
			'NI' => 'Nicaragua',
			'NE' => 'Niger',
			'NG' => 'Nigeria',
			'NU' => 'Niue',
			'NF' => 'Norfolk Island',
			'MP' => 'Northern Mariana Islands',
			'NO' => 'Norway',
			'OM' => 'Oman',
			'PK' => 'Pakistan',
			'PW' => 'Palau',
			'PS' => 'Palestine, State of',
			'PA' => 'Panama',
			'PG' => 'Papua New Guinea',
			'PY' => 'Paraguay',
			'PE' => 'Peru',
			'PH' => 'Philippines',
			'PN' => 'Pitcairn',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'PR' => 'Puerto Rico',
			'QA' => 'Qatar',
			'RE' => 'Réunion',
			'RO' => 'Romania',
			'RU' => 'Russian Federation',
			'RW' => 'Rwanda',
			'BL' => 'Saint BarthÉlemy',
			'SH' => 'Saint Helena, Ascension and Tristan Da Cunha',
			'KN' => 'Saint Kitts and Nevis',
			'LC' => 'Saint Lucia',
			'MF' => 'Saint Martin (French Part)',
			'PM' => 'Saint Pierre and Miquelon',
			'VC' => 'Saint Vincent and the Grenadines',
			'WS' => 'Samoa',
			'SM' => 'San Marino',
			'ST' => 'Sao Tome and Principe',
			'SA' => 'Saudi Arabia',
			'SN' => 'Senegal',
			'RS' => 'Serbia',
			'SC' => 'Seychelles',
			'SL' => 'Sierra Leone',
			'SG' => 'Singapore',
			'SX' => 'Sint Maarten (Dutch Part)',
			'SK' => 'Slovakia',
			'SI' => 'Slovenia',
			'SB' => 'Solomon Islands',
			'SO' => 'Somalia',
			'ZA' => 'South Africa',
			'GS' => 'South Georgia and the South Sandwich Islands',
			'SS' => 'South Sudan',
			'ES' => 'Spain',
			'LK' => 'Sri Lanka',
			'SD' => 'Sudan',
			'SR' => 'Suriname',
			'SJ' => 'Svalbard and Jan Mayen',
			'SZ' => 'Swaziland',
			'SE' => 'Sweden',
			'CH' => 'Switzerland',
			'SY' => 'Syrian Arab Republic',
			'TW' => 'Taiwan, Province of China',
			'TJ' => 'Tajikistan',
			'TZ' => 'Tanzania, United Republic of',
			'TH' => 'Thailand',
			'TL' => 'Timor-Leste',
			'TG' => 'Togo',
			'TK' => 'Tokelau',
			'TO' => 'Tonga',
			'TT' => 'Trinidad and Tobago',
			'TN' => 'Tunisia',
			'TR' => 'Turkey',
			'TM' => 'Turkmenistan',
			'TC' => 'Turks and Caicos Islands',
			'TV' => 'Tuvalu',
			'UG' => 'Uganda',
			'UA' => 'Ukraine',
			'AE' => 'United Arab Emirates',
			'GB' => 'United Kingdom',
			'US' => 'United States',
			'UM' => 'United States Minor Outlying Islands',
			'UY' => 'Uruguay',
			'UZ' => 'Uzbekistan',
			'VU' => 'Vanuatu',
			'VE' => 'Venezuela, Bolivarian Republic of',
			'VN' => 'Viet Nam',
			'VG' => 'Virgin Islands, British',
			'VI' => 'Virgin Islands, U.S.',
			'WF' => 'Wallis and Futuna',
			'EH' => 'Western Sahara',
			'YE' => 'Yemen',
			'ZM' => 'Zambia',
			'ZW' => 'Zimbabwe'
		);
		
		if ( $code ) {
			return ( isset( $data[$code] ) ) ? $data[$code] : '';
		} else {
			return $data;
		}
	} // end function
	
} // end class

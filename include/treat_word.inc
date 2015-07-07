<?php

function treat_word ($str2, $strip_ending = false) {
	$temp2 = '';
	$start_letter = '';
	$next_char = '';
	$result2 = '';

	$str2 = strtoupper($str2);

	if (($str2 == NULL) || (trim($str2) == '')) {
		return('');
	}
	$temp2 = $str2;

// Do some selective replacement on the leading letter/s only: ('soundalikes')
	if (preg_match('/^AE/', $temp2)) $temp2 = preg_replace('/^AE/', 'E', $temp2);
	elseif(preg_match('/^CN/', $temp2))  $temp2 = preg_replace('/^CN/', 'N', $temp2);
	elseif(preg_match('/^CT/', $temp2))  $temp2 = preg_replace('/^CT/', 'Z', $temp2);
	elseif(preg_match('/^CZ/', $temp2))  $temp2 = preg_replace('/^CZ/', 'V', $temp2);
	elseif(preg_match('/^DJ/', $temp2))  $temp2 = preg_replace('/^DJ/', 'J', $temp2);
	elseif(preg_match('/^EA/', $temp2))  $temp2 = preg_replace('/^EA/', 'E', $temp2);
	elseif(preg_match('/^EU/', $temp2))  $temp2 = preg_replace('/^EU/', 'U', $temp2);
	elseif(preg_match('/^GN/', $temp2))  $temp2 = preg_replace('/^GN/', 'N', $temp2);
	elseif(preg_match('/^KN/', $temp2))  $temp2 = preg_replace('/^KN/', 'N', $temp2);
	elseif(preg_match('/^MC/', $temp2))  $temp2 = preg_replace('/^MC/', 'MAC', $temp2);
	elseif(preg_match('/^MN/', $temp2))  $temp2 = preg_replace('/^MN/', 'N', $temp2);
	elseif(preg_match('/^OE/', $temp2))  $temp2 = preg_replace('/^OE/', 'E', $temp2);
	elseif(preg_match('/^QU/', $temp2))  $temp2 = preg_replace('/^QU/', 'Q', $temp2);
	elseif(preg_match('/^PS/', $temp2))  $temp2 = preg_replace('/^PS/', 'S', $temp2);
	elseif(preg_match('/^PT/', $temp2))  $temp2 = preg_replace('/^PT/', 'T', $temp2);
	elseif(preg_match('/^TS/', $temp2))  $temp2 = preg_replace('/^TS/', 'S', $temp2);
	elseif(preg_match('/^WR/', $temp2))  $temp2 = preg_replace('/^WR/', 'R', $temp2);
	elseif(preg_match('/^X/', $temp2))   $temp2 = preg_replace('/^X/', 'Z', $temp2);
	// This one is not in the list but in the old taxamatch file originally because of some fish names
	// having a silent H and hard P but we are putting it back in only in the beginning
	elseif(preg_match('/^ph/', $temp2))  $temp2 = preg_replace('/^ph/', 'f', $temp2);

	// Now keep the leading character, then do selected "soundalike" replacements. The
	// following letters are equated: AE, OE, E, U, Y and I; IA and A are equated;
	// K and C;  Z and S; and H is dropped. Also, A and O are equated, MAC and MC are equated, and SC and S.
	$start_letter = substr( $temp2, 0, 1);  // quarantine the leading letter
	$temp2 = substr( $temp2, 1);  // snip off the leading letter

	// now do the replacements
	$temp2 = str_ireplace ('AE', 'I', $temp2);
	$temp2 = str_ireplace ('IA', 'A', $temp2);
	$temp2 = str_ireplace ('OE', 'I', $temp2);
	$temp2 = str_ireplace ('OI', 'A', $temp2);
	$temp2 = str_ireplace ('SC', 'S', $temp2);
	$temp2 = str_ireplace ('E' , 'I', $temp2);
	$temp2 = str_ireplace ('O' , 'A', $temp2);
	$temp2 = str_ireplace ('U' , 'I', $temp2);
	$temp2 = str_ireplace ('Y' , 'I', $temp2);
	$temp2 = str_ireplace ('K' , 'C', $temp2);
	$temp2 = str_ireplace ('Z' , 'S', $temp2);
	$temp2 = str_ireplace ('H' , '', $temp2);
	//	$temp2 = str_ireplace ('io', 'a', $temp2);   // Not used in taxamatch?
	//	$temp2 = str_ireplace ('ou', 'u', $temp2);    // Not used in taxamatch?
	//	$temp2 = str_ireplace ('ph', 'f', $temp2);    // Not used in taxamatch?

	//add back the leading letter
	$temp2 = $start_letter . $temp2;

	// now drop any repeated characters (AA becomes A, BB or BBB becomes B, etc.)
	for ( $i = 0; $i <= strlen($temp2); $i++) {
		$next_char = substr( $temp2, $i, 1 );
		if ( $i == 0 ) {
			$result2 = $next_char;
		} elseif ( $next_char == substr( $result2, -1 ) ) {
		} else {
			$result2 = $result2 . $next_char;
		}
	}

	if ((strlen($result2) > 4) && ($strip_ending)) {
		// deal with variant endings -is (includes -us, -ys, -es), -im (was -um), -as (-os)
		// at end of string or word: translate all to -a
		if (substr($result2, -2) == 'IS') $result2 = preg_replace('/IS$/' , 'A', $result2);
		if (substr($result2, -2) == 'IM') $result2 = preg_replace('/IM$/' , 'A', $result2);
		if (substr($result2, -2) == 'AS') $result2 = preg_replace('/AS$/' , 'A', $result2);
	}

	return $result2;

}

?>

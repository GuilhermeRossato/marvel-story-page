<?php

/**
 * Checks whether a string finishes with a given substring.
 *
 * @param  string $haystack  The full string to be verified.
 * @param  string $needle    The intended final substring of the first parameter.
 * @return boolean           Whether $haystack ends with the substring in $needle.
 */
function endsWith($haystack, $needle) {
	$length = strlen($needle);

	return (substr($haystack, -$length, $length) === $needle);
}

/**
 * Checks whether a string strats with given substring.
 * @param  string $haystack  The full string tobe verified.
 * @param  string $needle    The intended starting substring of the first parameter.
 * @return boolean           Whether $haystack starts with the substring in $needle.
 */
function startsWith($haystack, $needle) {
	 $length = strlen($needle);

	 return (substr($haystack, 0, $length) === $needle);
}

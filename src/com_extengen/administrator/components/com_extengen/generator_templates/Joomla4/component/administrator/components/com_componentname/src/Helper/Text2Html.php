<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Administrator\Helper;

defined('_JEXEC') or die;

final class Text2Html
{
	/**
	 * Converts a plain text email to a passable HTML representation
	 *
	 * @param   string  $body  Plain text email
	 *
	 * @return  string  HTML verion of the email
	 *
	 * @since   3.3.1
	 */
	public static function convert($body)
	{
		$body = self::flatTextToHtml($body);
		$body = self::wpMakeClickable($body);

		return $body;
	}

	/**
	 * This can turn partially HTML text into a passable HTML document.
	 *
	 * @param   string  $text  Messy text.
	 *
	 * @return  string  Actual HTML code we can send in an email.
	 */
	private static function flatTextToHtml($text)
	{
		$text = trim($text);
		$text = str_replace(["\r\n", "\r"], ["\n", "\n"], $text);

		// Do I have a paragraph tag in the beginning of the comment?
		if (in_array(strtolower(substr($text, 0, 3)), ['<p>', '<p ']))
		{
			return $text;
		}

		// Do I have a DIV tag in the beginning of the comment?
		if (in_array(strtolower(substr($text, 0, 5)), ['<div>', '<div ']))
		{
			return $text;
		}

		$paragraphs = explode("\n\n", $text);

		return implode("\n", array_map(function ($text) {
			// Do I have a p tag?
			if (in_array(strtolower(substr($text, 0, 3)), ['<p>', '<p ']))
			{
				return $text;
			}

			// Do I have a div tag?
			if (in_array(strtolower(substr($text, 0, 5)), ['<div>', '<div ']))
			{
				return $text;
			}

			return "<p>" . $text . "</p>";
		}, $paragraphs));
	}

	// region Imported from WordPress

	/**
	 * Convert plaintext URI to HTML links.
	 *
	 * Converts URI, www and ftp, and email addresses. Finishes by fixing links
	 * within links.
	 *
	 * This method has been adapted from WordPress.
	 *
	 * @param   string  $text  Content to convert URIs.
	 *
	 * @return  string  Content with converted URIs.
	 * @since   3.3.1
	 */
	private static function wpMakeClickable($text)
	{
		$r = '';
		// split out HTML tags
		$textarr = preg_split('/(<[^<>]+>)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		// Keep track of how many levels link is nested inside <pre> or <code>
		$nested_code_pre = 0;

		foreach ($textarr as $piece)
		{

			if (preg_match('|^<code[\s>]|i', $piece) || preg_match('|^<pre[\s>]|i', $piece) || preg_match('|^<script[\s>]|i', $piece) || preg_match('|^<style[\s>]|i', $piece))
			{
				$nested_code_pre++;
			}
			elseif ($nested_code_pre && ('</code>' === strtolower($piece) || '</pre>' === strtolower($piece) || '</script>' === strtolower($piece) || '</style>' === strtolower($piece)))
			{
				$nested_code_pre--;
			}

			if ($nested_code_pre || empty($piece) || ($piece[0] === '<' && !preg_match('|^<\s*[\w]{1,20}+://|', $piece)))
			{
				$r .= $piece;
				continue;
			}

			// Long strings might contain expensive edge cases ...
			if (10000 < strlen($piece))
			{
				// ... break it up
				foreach (self::wpSplitStringByWhitespace($piece, 2100) as $chunk)
				{ // 2100: Extra room for scheme and leading and trailing paretheses
					if (2101 < strlen($chunk))
					{
						$r .= $chunk; // Too big, no whitespace: bail.
					}
					else
					{
						$r .= self::wpMakeClickable($chunk);
					}
				}
			}
			else
			{
				$ret = " $piece "; // Pad with whitespace to simplify the regexes

				$url_clickable = '~
				([\\s(<.,;:!?])                                        # 1: Leading whitespace, or punctuation
				(                                                      # 2: URL
					[\\w]{1,20}+://                                # Scheme and hier-part prefix
					(?=\S{1,2000}\s)                               # Limit to URLs less than about 2000 characters long
					[\\w\\x80-\\xff#%\\~/@\\[\\]*(+=&$-]*+         # Non-punctuation URL character
					(?:                                            # Unroll the Loop: Only allow puctuation URL character if followed by a non-punctuation URL character
						[\'.,;:!?)]                            # Punctuation URL character
						[\\w\\x80-\\xff#%\\~/@\\[\\]*(+=&$-]++ # Non-punctuation URL character
					)*
				)
				(\)?)                                                  # 3: Trailing closing parenthesis (for parethesis balancing post processing)
			~xS';
				// The regex is a non-anchored pattern and does not have a single fixed starting character.
				// Tell PCRE to spend more time optimizing since, when used on a page load, it will probably be used several times.

				$ret = preg_replace_callback($url_clickable, [__CLASS__, 'wpMakeURLClickableCallback'], $ret);

				$ret = preg_replace_callback('#([\s>])((www|ftp)\.[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]+)#is', [
					__CLASS__, 'wpMakeWebFTPClickableCallback',
				], $ret);
				$ret = preg_replace_callback('#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i', [
					__CLASS__, 'wpMakeEmailClickableCallback',
				], $ret);

				$ret = substr($ret, 1, -1); // Remove our whitespace padding.
				$r   .= $ret;
			}
		}

		// Cleanup of accidental links within links
		return preg_replace('#(<a([ \r\n\t]+[^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i', '$1$3</a>', $r);
	}

	/**
	 * Perform a deep string replace operation to ensure the values in $search are no longer present
	 *
	 * Repeats the replacement operation until it no longer replaces anything so as to remove "nested" values
	 * e.g. $subject = '%0%0%0DDD', $search ='%0D', $result ='' rather than the '%0%0DD' that
	 * str_replace would return
	 *
	 * This method has been adapted from WordPress.
	 *
	 * @param   string|array  $search   Needle.
	 * @param   string        $subject  Haystack.
	 *
	 * @return  string  The string with the replaced values.
	 * @since   3.3.1
	 */
	private static function wpDeepReplace($search, $subject)
	{
		$subject = (string) $subject;
		$count   = 1;

		while ($count)
		{
			$subject = str_replace($search, '', $subject, $count);
		}

		return $subject;
	}


	/**
	 * Checks and cleans a URL.
	 *
	 * This method has been adapted from WordPress.
	 *
	 * @param   string      $url        The URL to be cleaned.
	 * @param   array|null  $protocols  Optional. An array of acceptable protocols.
	 *
	 * @return  string  The cleaned $url
	 * @since   3.3.1
	 */
	private static function wpEscapeURL($url, $protocols = null)
	{
		if (empty($url))
		{
			return $url;
		}

		$url = str_replace(' ', '%20', ltrim($url));
		$url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\[\]\\x80-\\xff]|i', '', $url);

		if (empty($url))
		{
			return $url;
		}

		if (stripos($url, 'mailto:') !== 0)
		{
			$strip = ['%0d', '%0a', '%0D', '%0A'];
			$url   = self::wpDeepReplace($strip, $url);
		}

		$url = str_replace(';//', '://', $url);

		/* If the URL doesn't appear to contain a scheme, we
		 * presume it needs http:// prepended (unless a relative
		 * link starting with /, # or ? or a php file).
		 */

		if (strpos($url, ':') === false && !in_array($url[0], ['/', '#', '?']) &&
			!preg_match('/^[a-z0-9-]+?\.php/i', $url))
		{
			$url = 'http://' . $url;
		}

		if ((false !== strpos($url, '[')) || (false !== strpos($url, ']')))
		{
			$uri       = Uri::getInstance($url);
			$front     = $uri->toString(['scheme', 'user', 'pass', 'host', 'port']);
			$end_dirty = str_replace($front, '', $url);
			$end_clean = str_replace(['[', ']'], ['%5B', '%5D'], $end_dirty);
			$url       = str_replace($end_dirty, $end_clean, $url);

		}

		if ('/' !== $url[0])
		{
			$protocols = !empty($protocols) ? $protocols : [
				'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet', 'mms',
				'rtsp', 'sms', 'svn', 'tel', 'fax', 'xmpp', 'webcal', 'urn',
			];

			$uri = Uri::getInstance($url);

			if (!in_array($uri->getScheme(), $protocols))
			{
				return '';
			}
		}

		return $url;
	}


	/**
	 * Callback to convert URI match to HTML A element.
	 *
	 * This method has been adapted from WordPress.
	 *
	 * @param   array  $matches  Single Regex Match.
	 *
	 * @return  string  HTML A element with URI address.
	 * @since   3.3.1
	 */
	private static function wpMakeURLClickableCallback(array $matches)
	{
		$url = $matches[2];

		if (')' == $matches[3] && strpos($url, '('))
		{
			// If the trailing character is a closing parethesis, and the URL has an opening parenthesis in it, add the closing parenthesis to the URL.
			// Then we can let the parenthesis balancer do its thing below.
			$url    .= $matches[3];
			$suffix = '';
		}
		else
		{
			$suffix = $matches[3];
		}

		// Include parentheses in the URL only if paired
		while (substr_count($url, '(') < substr_count($url, ')'))
		{
			$suffix = strrchr($url, ')') . $suffix;
			$url    = substr($url, 0, strrpos($url, ')'));
		}

		$url = self::wpEscapeURL($url);

		if (empty($url))
		{
			return $matches[0];
		}

		return $matches[1] . "<a href=\"$url\">$url</a>" . $suffix;
	}

	/**
	 * Breaks a string into chunks by splitting at whitespace characters.
	 *
	 * The length of each returned chunk is as close to the specified length goal as possible,
	 * with the caveat that each chunk includes its trailing delimiter.
	 * Chunks longer than the goal are guaranteed to not have any inner whitespace.
	 *
	 * Joining the returned chunks with empty delimiters reconstructs the input string losslessly.
	 *
	 * Input string must have no null characters (or eventual transformations on output chunks must not care about null
	 * characters)
	 *
	 *     _split_str_by_whitespace( "1234 67890 1234 67890a cd 1234   890 123456789 1234567890a    45678   1 3 5 7 90
	 *     ", 10 ) == array (
	 *         0 => '1234 67890 ',  // 11 characters: Perfect split
	 *         1 => '1234 ',        //  5 characters: '1234 67890a' was too long
	 *         2 => '67890a cd ',   // 10 characters: '67890a cd 1234' was too long
	 *         3 => '1234   890 ',  // 11 characters: Perfect split
	 *         4 => '123456789 ',   // 10 characters: '123456789 1234567890a' was too long
	 *         5 => '1234567890a ', // 12 characters: Too long, but no inner whitespace on which to split
	 *         6 => '   45678   ',  // 11 characters: Perfect split
	 *         7 => '1 3 5 7 90 ',  // 11 characters: End of $string
	 *     );
	 *
	 * This method has been adapted from WordPress.
	 *
	 * @param   string  $string  The string to split.
	 * @param   int     $goal    The desired chunk length.
	 *
	 * @return  array  Numeric array of chunks.
	 * @since   3.3.1
	 *
	 */
	private static function wpSplitStringByWhitespace($string, $goal)
	{
		$chunks = [];

		$string_nullspace = strtr($string, "\r\n\t\v\f ", "\000\000\000\000\000\000");

		while ($goal < strlen($string_nullspace))
		{
			$pos = strrpos(substr($string_nullspace, 0, $goal + 1), "\000");

			if (false === $pos)
			{
				$pos = strpos($string_nullspace, "\000", $goal + 1);
				if (false === $pos)
				{
					break;
				}
			}

			$chunks[]         = substr($string, 0, $pos + 1);
			$string           = substr($string, $pos + 1);
			$string_nullspace = substr($string_nullspace, $pos + 1);
		}

		if ($string)
		{
			$chunks[] = $string;
		}

		return $chunks;
	}

	/**
	 * Callback to convert URL match to HTML A element.
	 *
	 * This method has been adapted from WordPress.
	 *
	 * @param   array  $matches  Single Regex Match.
	 *
	 * @return  string  HTML A element with URL address.
	 * @since   3.3.1
	 *
	 */
	private static function wpMakeWebFTPClickableCallback(array $matches)
	{
		$ret  = '';
		$dest = $matches[2];
		$dest = 'http://' . $dest;

		// removed trailing [.,;:)] from URL
		if (in_array(substr($dest, -1), ['.', ',', ';', ':', ')']))
		{
			$ret  = substr($dest, -1);
			$dest = substr($dest, 0, strlen($dest) - 1);
		}

		$dest = self::wpEscapeURL($dest);

		if (empty($dest))
		{
			return $matches[0];
		}

		return $matches[1] . "<a href=\"$dest\">$dest</a>$ret";
	}

	/**
	 * Callback to convert email address match to HTML A element.
	 *
	 * This method has been adapted from WordPress.
	 *
	 * @param   array  $matches  Single Regex Match.
	 *
	 * @return  string  HTML A element with email address.
	 * @since   3.3.1
	 */
	private static function wpMakeEmailClickableCallback(array $matches)
	{
		$email = $matches[2] . '@' . $matches[3];

		return $matches[1] . "<a href=\"mailto:$email\">$email</a>";
	}

	// endregion

}
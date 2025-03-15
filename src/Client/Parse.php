<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Client;

use WP_MatchesMapRegex;

// No direct access
defined("ABSPATH") or die("Hey, do not do this 😱");

class Parse {
	/**
	 * Parses the permalink to find the correct WordPress query.
	 *
	 * Sets up the query variables based on the request. There are also many
	 * filters and actions that can be used to further manipulate the result.
	 *
	 * This function is based on the `parse_request` method in WP class.
	 *
	 * @global WP $wp Current WordPress environment instance.
	 * @global WP_Rewrite $wp_rewrite WordPress rewrite component.
	 *
	 * @param string $permalink The requested permalink.
	 * @param array<mixed>|string $extra_query_vars Set the extra query variables.
	 * @return bool Whether the request was parsed.
	 */
	public static function parsePermalink($permalink, $extra_query_vars = "") {
		global $wp, $wp_rewrite;

		/**
		 * Filters whether to parse the request.
		 *
		 * @since 3.5.0
		 *
		 * @param bool         $bool             Whether or not to parse the request. Default true.
		 * @param WP           $wp               Current WordPress environment instance.
		 * @param array|string $extra_query_vars Extra passed query variables.
		 */
		if (!apply_filters("do_parse_request", true, $wp, $extra_query_vars)) {
			return false;
		}

		$wp->query_vars = [];
		$post_type_query_vars = [];

		if (is_array($extra_query_vars)) {
			$wp->extra_query_vars = &$extra_query_vars;
		} elseif (!empty($extra_query_vars)) {
			parse_str($extra_query_vars, $wp->extra_query_vars);
		}
		// Process PATH_INFO, REQUEST_URI, and 404 for permalinks.

		// Fetch the rewrite rules.
		$rewrite = $wp_rewrite->wp_rewrite_rules();

		if (!empty($rewrite)) {
			// If we match a rewrite rule, this will be cleared.
			$error = "404";
			$wp->did_permalink = true;

			$pathinfo = isset($_SERVER["PATH_INFO"]) ? $_SERVER["PATH_INFO"] : "";
			/* @phpstan-ignore-next-line */
			[$pathinfo] = explode("?", $pathinfo);
			$pathinfo = str_replace("%", "%25", $pathinfo);

			// [$req_uri] = explode("?", $_SERVER["REQUEST_URI"]);
			[$req_uri] = explode("?", $permalink);

			$self = $_SERVER["PHP_SELF"];

			$home_path = parse_url(home_url(), PHP_URL_PATH);
			$home_path_regex = "";
			if (is_string($home_path) && "" !== $home_path) {
				$home_path = trim($home_path, "/");
				$home_path_regex = sprintf("|^%s|i", preg_quote($home_path, "|"));
			}

			/*
			 * Trim path info from the end and the leading home path from the front.
			 * For path info requests, this leaves us with the requesting filename, if any.
			 * For 404 requests, this leaves us with the requested permalink.
			 */
			$req_uri = str_replace($pathinfo, "", $req_uri);
			$req_uri = trim($req_uri, "/");
			$pathinfo = trim($pathinfo, "/");
			/* @phpstan-ignore-next-line */
			$self = trim($self, "/");

			if (!empty($home_path_regex)) {
				$req_uri = preg_replace($home_path_regex, "", $req_uri);
				/** @phpstan-ignore-next-line */
				$req_uri = trim($req_uri, "/");

				$pathinfo = preg_replace($home_path_regex, "", $pathinfo);
				/** @phpstan-ignore-next-line */
				$pathinfo = trim($pathinfo, "/");

				$self = preg_replace($home_path_regex, "", $self);
				/** @phpstan-ignore-next-line */
				$self = trim($self, "/");
			}

			// The requested permalink is in $pathinfo for path info requests and $req_uri for other requests.
			if (!empty($pathinfo) && !preg_match("|^.*" . $wp_rewrite->index . '$|', $pathinfo)) {
				$requested_path = $pathinfo;
			} else {
				// If the request uri is the index, blank it out so that we don't try to match it against a rule.
				if ($req_uri === $wp_rewrite->index) {
					$req_uri = "";
				}

				$requested_path = $req_uri;
			}

			$requested_file = $req_uri;

			$wp->request = $requested_path;

			// Look for matches.
			$request_match = $requested_path;
			if (empty($request_match)) {
				// An empty request could only match against ^$ regex.
				if (isset($rewrite['$'])) {
					$wp->matched_rule = '$';
					$query = $rewrite['$'];
					$matches = [""];
				}
			} else {
				foreach ((array) $rewrite as $match => $query) {
					// If the requested file is the anchor of the match, prepend it to the path info.
					if (
						!empty($requested_file) &&
						str_starts_with($match, $requested_file) &&
						$requested_file !== $requested_path
					) {
						$request_match = $requested_file . "/" . $requested_path;
					}

					if (
						preg_match("#^$match#", $request_match, $matches) ||
						preg_match("#^$match#", urldecode($request_match), $matches)
					) {
						if (
							$wp_rewrite->use_verbose_page_rules &&
							preg_match('/pagename=\$matches\[([0-9]+)\]/', $query, $varmatch)
						) {
							// This is a verbose page match, let's check to be sure about it.
							$page = get_page_by_path($matches[$varmatch[1]]);

							if (!$page) {
								continue;
							}

							$post_status_obj = get_post_status_object($page->post_status);

							if (
								/** @phpstan-ignore-next-line */
								!$post_status_obj->public &&
								/** @phpstan-ignore-next-line */
								!$post_status_obj->protected &&
								/** @phpstan-ignore-next-line */
								!$post_status_obj->private &&
								/** @phpstan-ignore-next-line */
								$post_status_obj->exclude_from_search
							) {
								continue;
							}
						}

						// Got a match.
						$wp->matched_rule = $match;
						break;
					}
				}
			}

			if (!empty($wp->matched_rule)) {
				// Trim the query of everything up to the '?'.
				/** @phpstan-ignore-next-line */
				$query = preg_replace("!^.+\?!", "", $query);

				// Substitute the substring matches into the query.
				/** @phpstan-ignore-next-line */
				$query = addslashes(WP_MatchesMapRegex::apply($query, $matches));

				/** @phpstan-ignore-next-line */
				$wp->matched_query = $query;

				// Parse the query.
				parse_str($query, $perma_query_vars);

				// If we're processing a 404 request, clear the error var since we found something.
				/* @phpstan-ignore-next-line */
				if ("404" === $error) {
					unset($error, $_GET["error"]);
				}
			}

			// If req_uri is empty or if it is a request for ourself, unset error.
			if (
				empty($requested_path) ||
				$requested_file === $self ||
				/* @phpstan-ignore-next-line */
				str_contains($_SERVER["PHP_SELF"], "wp-admin/")
			) {
				unset($error, $_GET["error"]);

				/* @phpstan-ignore-next-line */
				if (isset($perma_query_vars) && str_contains($_SERVER["PHP_SELF"], "wp-admin/")) {
					unset($perma_query_vars);
				}

				$wp->did_permalink = false;
			}
		}

		/**
		 * Filters the query variables allowed before processing.
		 *
		 * Allows (publicly allowed) query vars to be added, removed, or changed prior
		 * to executing the query. Needed to allow custom rewrite rules using your own arguments
		 * to work, or any other custom query variables you want to be publicly available.
		 *
		 * @since 1.5.0
		 *
		 * @param string[] $public_query_vars The array of allowed query variable names.
		 */
		$wp->public_query_vars = apply_filters("query_vars", $wp->public_query_vars);

		foreach (get_post_types([], "objects") as $post_type => $t) {
			if (is_post_type_viewable($t) && $t->query_var) {
				$post_type_query_vars[$t->query_var] = $post_type;
			}
		}

		foreach ($wp->public_query_vars as $wpvar) {
			if (isset($wp->extra_query_vars[$wpvar])) {
				$wp->query_vars[$wpvar] = $wp->extra_query_vars[$wpvar];
			} elseif (isset($_GET[$wpvar]) && isset($_POST[$wpvar]) && $_GET[$wpvar] !== $_POST[$wpvar]) {
				wp_die(
					__("A variable mismatch has been detected."),
					__("Sorry, you are not allowed to view this item."),
					400,
				);
			} elseif (isset($_POST[$wpvar])) {
				$wp->query_vars[$wpvar] = $_POST[$wpvar];
			} elseif (isset($_GET[$wpvar])) {
				$wp->query_vars[$wpvar] = $_GET[$wpvar];
			} elseif (isset($perma_query_vars[$wpvar])) {
				$wp->query_vars[$wpvar] = $perma_query_vars[$wpvar];
			}

			if (!empty($wp->query_vars[$wpvar])) {
				if (!is_array($wp->query_vars[$wpvar])) {
					/* @phpstan-ignore-next-line */
					$wp->query_vars[$wpvar] = (string) $wp->query_vars[$wpvar];
				} else {
					foreach ($wp->query_vars[$wpvar] as $vkey => $v) {
						if (is_scalar($v)) {
							$wp->query_vars[$wpvar][$vkey] = (string) $v;
						}
					}
				}

				if (isset($post_type_query_vars[$wpvar])) {
					$wp->query_vars["post_type"] = $post_type_query_vars[$wpvar];
					$wp->query_vars["name"] = $wp->query_vars[$wpvar];
				}
			}
		}

		// Convert urldecoded spaces back into '+'.
		foreach (get_taxonomies([], "objects") as $taxonomy => $t) {
			if ($t->query_var && isset($wp->query_vars[$t->query_var])) {
				/* @phpstan-ignore-next-line */
				$wp->query_vars[$t->query_var] = str_replace(" ", "+", $wp->query_vars[$t->query_var]);
			}
		}

		// Don't allow non-publicly queryable taxonomies to be queried from the front end.
		if (!is_admin()) {
			foreach (get_taxonomies(["publicly_queryable" => false], "objects") as $taxonomy => $t) {
				/*
				 * Disallow when set to the 'taxonomy' query var.
				 * Non-publicly queryable taxonomies cannot register custom query vars. See register_taxonomy().
				 */
				if (isset($wp->query_vars["taxonomy"]) && $taxonomy === $wp->query_vars["taxonomy"]) {
					unset($wp->query_vars["taxonomy"], $wp->query_vars["term"]);
				}
			}
		}

		// Limit publicly queried post_types to those that are 'publicly_queryable'.
		if (isset($wp->query_vars["post_type"])) {
			$queryable_post_types = get_post_types(["publicly_queryable" => true]);

			if (!is_array($wp->query_vars["post_type"])) {
				if (!in_array($wp->query_vars["post_type"], $queryable_post_types, true)) {
					unset($wp->query_vars["post_type"]);
				}
			} else {
				$wp->query_vars["post_type"] = array_intersect(
					$wp->query_vars["post_type"],
					$queryable_post_types,
				);
			}
		}

		// Resolve conflicts between posts with numeric slugs and date archive queries.
		$wp->query_vars = wp_resolve_numeric_slug_conflicts($wp->query_vars);

		foreach ((array) $wp->private_query_vars as $var) {
			if (isset($wp->extra_query_vars[$var])) {
				$wp->query_vars[$var] = $wp->extra_query_vars[$var];
			}
		}

		if (isset($error)) {
			$wp->query_vars["error"] = $error;
		}

		/**
		 * Filters the array of parsed query variables.
		 *
		 * @since 2.1.0
		 *
		 * @param array $query_vars The array of requested query variables.
		 */
		$wp->query_vars = apply_filters("request", $wp->query_vars);

		/**
		 * Fires once all query variables for the current request have been parsed.
		 *
		 * @since 2.1.0
		 *
		 * @param WP $wp Current WordPress environment instance (passed by reference).
		 */
		do_action_ref_array("parse_request", [&$wp]);

		// Set up the WordPress query and other globals
		$wp->query_posts();
		$wp->handle_404();
		$wp->register_globals();

		return true;
	}
}

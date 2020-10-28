<?php

use GraphQLRelay\Relay;

global $post;
$post_id  = $post->ID;

// get the latest revision
$revision = array_values( wp_get_post_revisions( $post_id ) )[0]
	// or if revisions are disabled, get the autosave
	?? wp_get_post_autosave( $post_id, get_current_user_id() )
	?? null;

$post_type_object = \get_post_type_object( $post->post_type );

$global_relay_id = Relay::toGlobalId(
	'post',
	absint( $post_id )
);

$referenced_node_single_name
	= $post_type_object->graphql_single_name ?? null;

$post_url = get_the_permalink( $post );
$path     = str_ireplace( get_home_url(), '', $post_url );

// if the post parent has a ? in it's url, this is a new draft
// and or the post has no proper permalink that Gatsby can use.
// so we will create one /post_graphql_name/post_db_id
// this same logic is on the Gatsby side to account for this situation.
if ( strpos( $path, '?' ) ) {
	$path = "/$referenced_node_single_name/$post_id";
}

$preview_url  = \WPGatsby\Admin\Preview::get_gatsby_preview_instance_url();
$preview_url  = rtrim( $preview_url, '/' );
$frontend_url = "$preview_url$path";
?>

<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>Preview</title>
	<style>
		#loader {
			position: fixed;
			top: 32px;
			bottom: 0;
			left: 0;
			right: 0;
			width: 100%;
			height: 100%;
			height: calc(100% - 46px);
			background: white;	
			z-index: 100;
			text-align: center;
			display: flex;
			justify-content: center;
			flex-direction: column;
			opacity: 1;
			transition: .125s ease-out opacity;
			padding: 0 50px;
    		box-sizing: border-box;
		}

		@media (max-width: 782px) {
			#loader {
				top: 46px;
			}
		}

		#loader.loaded {
			opacity: 0;
		}

		button {
			font-size: 1rem;
			padding: 15px 30px;
			cursor: pointer;
			font-weight: medium;
			color: white !important;
			background: rebeccapurple;
			border: none;
			margin-top: 20px;
			letter-spacing: 0.25px;
		}

		h1, h2, pre, p, button {
			font-family: "Futura PT", -apple-system, "BlinkMacSystemFont", "Segoe UI", "Roboto", "Helvetica Neue", "Arial", "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
			line-height: 1.45
		}

		b, p, button {
			color: #272727
		}

		h1, h2 {
			color: rebeccapurple;
		}

		h1 {
			display: block;
			font-size: 2em;
			margin-block-start: 0.67em;
			margin-block-end: 0.67em;
			margin-inline-start: 0px;
			margin-inline-end: 0px;
			font-weight: bold;
		}

		body {
			font-size: 18px;
		}

		#gatsby-loading-logo {
			max-width: 80%;
			width: 64px;
			margin: 0 auto;
			margin-bottom: 10px;
			box-shadow: 0 0 0 rgba(102, 51, 153, 0.4);
			-webkit-animation: pulse 2s infinite;
			animation: pulse 2s infinite;
			border-radius: 50%;
		}

        @-webkit-keyframes pulse {
          0% {
            box-shadow: 0 0 0 0 rgba(102, 51, 153, 0.4);
          }
          70% {
            box-shadow: 0 0 0 30px rgba(102, 51, 153, 0);
          }
          100% {
            box-shadow: 0 0 0 0 rgba(102, 51, 153, 0);
          }
        }

        @keyframes pulse {
          0% {
            box-shadow: 0 0 0 0 rgba(102, 51, 153, 0.4);
          }
          70% {
            box-shadow: 0 0 0 30px rgba(102, 51, 153, 0);
          }
          100% {
            box-shadow: 0 0 0 0 rgba(102, 51, 153, 0);
          }
        }
      

		.content {
			width: 100%;
			left: 0;
			padding-top: 46px;
			padding-bottom: 70px;
			min-height: 100%;
			min-height: calc(100vh - 46px);
			box-sizing: border-box;

			display: flex;
			justify-content: center;
			align-items: center;
			flex-direction: column;
		}

		#preview-loader-warning, .content {
			max-width: 80%;
			width: 800px;
			margin: 0 auto;
		}

		.content p {
			margin: 0 auto;
		}

		iframe {
			position: fixed;

			width: 100%;
			left: 0;

			top: 46px;
			height: 100%;
			height: calc(100vh - 46px);
		}

		@media (min-width: 783px) {
			iframe {
				top: 32px;
				height: calc(100vh - 32px);
			}
		}

		pre {
			white-space: pre-wrap;       /* css-3 */
			white-space: -moz-pre-wrap;  /* Mozilla, since 1999 */
			white-space: -pre-wrap;      /* Opera 4-6 */
			white-space: -o-pre-wrap;    /* Opera 7 */
			word-wrap: break-word;       /* Internet Explorer 5.5+ */

			background: #f1f1f1;
			padding: 50px 40px 40px 80px;
			margin-bottom: 50px;

			position: relative;
			max-width: 640px;
			box-sizing: border-box;
		}

		br {
			line-height: 1.75
		}

		#error-message-element::before {
			content: 'Error';
			position: absolute;
			top: 0;
			left: 0;
			padding: 5px 10px;
			background: rebeccapurple;
			color: white;
			font-size: 12px;
		}

		a[target="_blank"]::after {
			content: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAQElEQVR42qXKwQkAIAxDUUdxtO6/RBQkQZvSi8I/pL4BoGw/XPkh4XigPmsUgh0626AjRsgxHTkUThsG2T/sIlzdTsp52kSS1wAAAABJRU5ErkJggg==);
			margin: 0 3px 0 5px;
		}
		
	</style>

	<script>
		try {
			var initialState = {
				nodeId: "<?php echo $global_relay_id; ?>",
				modified: "<?php echo $revision->post_modified ?? null; ?>",
				previewFrontendUrl: "<?php echo $preview_url; ?>",
				revision: <?php echo json_encode($revision); ?>,
				post: <?php echo json_encode($post); ?>,
			}

			console.log({ initialState });

			function updateLoaderWarning(message) {
				var previewWarningP = document.getElementById("preview-loader-warning")

				previewWarningP.innerHTML = message + `<br /><br /><button onclick="cancelPreviewLoader()">Cancel and Troubleshoot</button>`
				previewWarningP.style.display = 'initial'
			}

			var timeoutSeconds = 45

			var timeoutWarning = setTimeout(() => {
				updateLoaderWarning(
					`Preview is taking a very long time to load (more than ${timeoutSeconds} seconds).<br />Try pressing "preview" again from the WordPress edit screen.<br />If you see this again, your preview builds are either very slow or there's something wrong.`
				)
			}, 1000 * timeoutSeconds)

			function cancelPreviewLoader() {
				showError(`Preview was cancelled.`)
			}

			function fetchPreviewStatusAndUpdateUI({ 
				ignoreNoIndicationOfSourcing = false 
			} = {}) {
				fetch(
					initialState.previewFrontendUrl + `/__wpgatsby-preview-status`, 
					{
						method: 'POST',
						body: JSON.stringify({  
							nodeId: initialState.nodeId,
							modified: initialState.modified,
							ignoreNoIndicationOfSourcing
						}),
						headers: {
							'Content-Type': 'application/json'
						}
					}
				)
				.then(response => response.json())
				.then(response => {
					onPreviewReady(response)
				})
				.catch(errorMessage => {
					if (
						typeof errorMessage === `string` &&
						errorMessage.str_post(`Unexpected token < in JSON at position 0`)
					) {
						errorMessage += `\n\nYour version of gatsby-source-wordpress-experimental is likely out of date.\n\nPlease upgrade to the latest version.`
					}
					showError(errorMessage)
				})
			}

			fetchPreviewStatusAndUpdateUI()

			var noIndicationOfSourcingWarningTimeout

			function displayNoIndicationOfSourcingWarningAndTryAgain() {
				noIndicationOfSourcingWarningTimeout = setTimeout(() => {
					updateLoaderWarning(
						`There is no indication that preview data is being sourced by the server.<br /><br />A code change for this website might be deploying and blocking preview from working.<br /><br /><b>Please try pressing "preview" in WordPress again.</b><br />If you see this message again, wait 5 - 10 minutes and then try again,<br />or contact your developer for help.`
					)
				}, 0)

				fetchPreviewStatusAndUpdateUI({ ignoreNoIndicationOfSourcing: true })
			}

			function onPreviewReady(response) {
				clearTimeout(timeoutWarning)

				if (response.type && response.type === `NOT_IN_PREVIEW_MODE`) {
					throw new Error(`Your Gatsby site is not in Preview mode.\nIf you're hosting on Gatsby Cloud, please see below for where to contact support.\nIf you're running Previews locally or are self-hosting, please refer to https://www.gatsbyjs.com/docs/refreshing-content to put Gatsby into Preview mode.`)
				}

				if (response.type && response.type === `NO_INDICATION_OF_SOURCING`) {
					return displayNoIndicationOfSourcingWarningAndTryAgain()
				}

				console.log(`Received a response:`);
				console.log(response);

				if (
					!response.type ||
					!response.payload ||
					!response.payload.pageNode ||
					!response.payload.pageNode.path
				) {
					throw new Error(
						`Received an improper response from the Preview server.`
					)
				}

				var previewIframe = document.getElementById('preview');

				
				previewIframe.addEventListener('load', onIframeLoaded)

				previewIframe.src = initialState.previewFrontendUrl + response.payload.pageNode.path;

				if (!noIndicationOfSourcingWarningTimeout) {
					clearTimeout(noIndicationOfSourcingWarningTimeout)
				}
			}

			function onIframeLoaded() {
				var loader = document.getElementById('loader');

				// this delay prevents a flash between 
				// the iframe painting and the loader dissapearing
				setTimeout(() => {
					// there is a fadeout css animation on this
					loader.classList.add("loaded")

					setTimeout(() => {
						// we wait a sec to display none so the css animation fadeout can complete
						loader.style.display = 'none'
					}, 100)
				}, 50);
			}
		} catch(e) {
			document.addEventListener('DOMContentLoaded', () => {
				showError(e)
			})
		}

        function showError(error) {
			const iframe = document.getElementById('preview')
			iframe.style.display = "none"
			
			const loader = document.getElementById('loader')
			loader.style.display = "none"
			
			const errorElement = document.getElementById('error-message-element')
			errorElement.textContent = error

			const content = document.querySelector('.content.error')
			content.style.display = "block"                
        }
	</script>
</head>

<body>

<div id="loader">
	<div id="gatsby-loading-logo">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 28" focusable="false" class="logo">
		<title>Gatsby Logo</title>
		<circle cx="14" cy="14" r="14" fill="#639" data-darkreader-inline-fill="" style="--darkreader-inline-fill:#52297a;"></circle>
		<path fill="#fff" d="M6.2 21.8C4.1 19.7 3 16.9 3 14.2L13.9 25c-2.8-.1-5.6-1.1-7.7-3.2zm10.2 2.9L3.3 11.6C4.4 6.7 8.8 3 14 3c3.7 0 6.9 1.8 8.9 4.5l-1.5 1.3C19.7 6.5 17 5 14 5c-3.9 0-7.2 2.5-8.5 6L17 22.5c2.9-1 5.1-3.5 5.8-6.5H18v-2h7c0 5.2-3.7 9.6-8.6 10.7z" data-darkreader-inline-fill="" style="--darkreader-inline-fill:#181a1b;"></path>
        </svg>
	</div>
	<h1>Loading Preview</h1>
	<p id="preview-loader-warning" style="display: none;"></p>
</div>

<iframe id='preview' frameborder="0"></iframe>

<div class="content error" style="display: none;">
	<h1>Preview broken</h1>
	<p>
		The Preview frontend url set on the
		 <a
				href="<?php echo get_bloginfo( 'url' ); ?>/wp-admin/options-general.php?page=gatsbyjs"
				target="_blank" rel="noopener, nofollow. noreferrer, noopener, external"
		>settings
			page</a> isn't working properly.
		<br>
		<br>
		<b>Preview URL: </b>
		<a 
			href="<?php echo $preview_url; ?>"
			target="_blank" rel="noopener, nofollow. noreferrer, noopener, external"
		>
			<?php echo $preview_url; ?>
		</a>
	</p>
	<br>
	<pre id="error-message-element"></pre>
	<h2>Troubleshooting</h2>
	<p>
		Please ensure your URL is correct and your Preview instance is up and running.
		<br>
		<br>
		If you've set the correct URL, your Preview instance is currently running, and you're still having trouble, please <a
				href="https://www.gatsbyjs.com/cloud/docs/wordpress/getting-started/" target="_blank"
				rel="noopener, nofollow. noreferrer, noopener, external">refer to the docs</a> for
		troubleshooting steps, ask your developer, or <a href="https://www.gatsbyjs.com/contact-us/" target="_blank"
									rel="noopener, nofollow. noreferrer, noopener, external">contact
			support</a> if that doesn't solve your issue.
		<br>
		<br>
		If you don't have a valid Gatsby Preview instance, you can <a
				href="https://www.gatsbyjs.com/preview/" target="_blank"
				rel="noopener, nofollow. noreferrer, noopener, external">set one up now on Gatsby
			Cloud.</a>
	</p>
	<h2>Developer instructions</h2>
	<p>Please visit 
		<a
			href="https://github.com/gatsbyjs/gatsby-source-wordpress-experimental/blob/master/docs/tutorials/configuring-wp-gatsby.md#setting-up-preview" target="_blank"
			rel="noopener, nofollow. noreferrer, noopener, external"
		>
		the docs
		</a> for instructions on setting up Gatsby Preview.
	</p>
</div>
</body>

</html>
<?php wp_footer(); ?>

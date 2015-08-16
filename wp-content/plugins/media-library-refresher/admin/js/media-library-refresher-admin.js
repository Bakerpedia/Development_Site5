(function( $ ) {
	var attachmentids = [];
	var numofattachments = 0;
	var offset = 0;

	$( 'button.scan').click(function() {

		$('.loader').css('background','url("'+ mlr.plugin_dir_path +'img/ajax.gif" )');
		$('.loader').css('display','block');


		function doAjaxImport(offset) {
			if (offset == numofattachments) {
				$('.loader').css('display','none');
				$('.check').css('background','url("'+ mlr.plugin_dir_path +'img/check.png" )');
				$('.check').css('display','block');
				$('.linking-results > h3').html('Refreshed Media Items ' + offset + ' of ' + numofattachments);
			}
			else {
				var data = {
					action: 'mlr_refresh_item',
					attachmentid: attachmentids[offset],
					"offset": offset,
					security: mlr.nonce
				};
				$.post(ajaxurl, data, ajaxImportCallback);
			}
		}
		function ajaxImportCallback(response_raw) {
			var response = JSON.parse(response_raw);

			$('.first-num').html(response.new_offset);
			if (response.attachment_title) {
				$('.updates').prepend('<li><strong>Refreshed:</strong> '+ response.attachment_title +'</li>');
			}
			doAjaxImport(response.new_offset);
		}

		var data = {
			action: 'mlr_get_media_items',
			post_id: mlr.post_id,
			security: mlr.nonce
		};
		$.ajax({
			type: "post",
			url: ajaxurl,
			data: data,
			success: function(data) {
				attachmentids = JSON.parse(data);
				numofattachments = attachmentids.length;
				$('.variable-media-items').html(numofattachments);
				$('.loader').css('display','none');
				$('.scan-begin').css('display','none');
				$('.scan-results').css('display','block');
				$('.begin-linking').css('display','block');

				$('button.media_items').click(function(){
					$('button.media_items').css('display','none');
					$('.scan-results').css('display','none');
					$('.loader').css('display','block');
					$('.second-num').html(numofattachments);
					$('.linking-results').css('display','block');
					doAjaxImport(0);
				});
			}
		});
		return false;
	});

})( jQuery );

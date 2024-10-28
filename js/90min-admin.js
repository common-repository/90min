// 90min-admin.js
var nm_main = function($) {
	var NM = window.NM || {};

	var leagues = NM.leagues.all,
		savedLeagues = NM.leagues.saved,
		savedCategories = NM.savedCategories,
		$settingsForm = $('#nm-settings-form'),
		$languageField = $('.nmin-settings-language', $settingsForm),
		$leaguesSelectbox = $('.nmin-settings-leagues', $settingsForm),
		$categoriesContainer = $('.nmin-settings-categories', $settingsForm),
		$authButton = $('.nm-authentication', $settingsForm),
		$authMessage = $('.nm-authentication-results', $settingsForm),
		$authSpinner = $('.nm-spinner', $settingsForm),
		selectedLanguageCode = $languageField.val();

	NM.init = function() {
		// just populate
		NM.populateLeagues(selectedLanguageCode);
		NM.populateCategories(selectedLanguageCode);

		// also do this on language change event
		$languageField.change(function(e) {
			var languageCode = $(this).val();
			// re-render the leagues selectbox
			NM.populateLeagues( languageCode );
			NM.populateCategories( languageCode );
		});

		// setup click events for leagues
		$leaguesSelectbox.on('click', '.nm-opt-group input', function() {
			var leagueID = $(this).data('league-id'),
				isChecked = $(this).is(':checked'),
				$found = $leaguesSelectbox.find('.nm-opt-option[data-nm-league-id=' + leagueID + '] input');

			$found.prop( 'checked', isChecked );
		});

		// auth form
		NM.setupAuthForm();
	};

	NM.setupAuthForm = function() {
		$authButton.click( function(e) {
			e.preventDefault(); // do not submit form

			var payload = {
				partner_id: $('.nmin-settings-partner-id', $settingsForm).val(),
				api_key: $('.nmin-settings-api-key', $settingsForm).val(),
				nonce: $('.nmin-settings-ajax-auth-nonce', $settingsForm).val(),
				action: '90min-auth'
			};

			// start spinner
			var spinnerClass = 'nm-show';
			$authSpinner.toggleClass(spinnerClass);

			// empty text
			$authMessage.empty();

			console.log('before auth', payload);

			$.post( ajaxurl, payload, function(data) {
				var theHTML;
				$authSpinner.toggleClass(spinnerClass);

				if (data.success) {
					// <span class="dashicons dashicons-yes"></span>
					theHTML = '<span class="dashicons dashicons-yes"></span> ' + NM.strings.auth.success;
				} else {
					theHTML = '<span class="dashicons dashicons-no"></span> ' + NM.strings.auth.failure;
				}

				$authMessage.html( $('<span>', {
					class: data.success ? 'nm-success nm-showanyway' : 'nm-failure nm-showanyway'
				}).append(theHTML) );
			});
		});
	};

	NM.populateCategories = function(languageCode) {
		if ( _.isEmpty(leagues) || !languageCode || !_.has(leagues, languageCode) )
			return;

		// if this language has no categories, end
		if ( !_.has(leagues[languageCode], 'categories') )
			return;

		// empty first
		$categoriesContainer.empty();

		$.each( leagues[languageCode]['categories'], function(k, v) {
			var $theLabel = $('<label>', {
				text: v
			});

			$theLabel.prepend( $('<input>', {
				type: 'checkbox',
				name: $categoriesContainer.data('name') + '[]',
				val: v,
				checked: _.contains(savedCategories, v.toString())
			}) );

			$categoriesContainer.append($theLabel);
		});
	};

	/**
	 * Renders and populates the leagues milti-select input
	 */
	NM.populateLeagues = function(languageCode) {
		if ( _.isEmpty(leagues) || !languageCode || !_.has(leagues, languageCode) )
			return;

		var $label, $option;

		// empty first
		$leaguesSelectbox.empty();

		var name = $leaguesSelectbox.data('item-name');

		// loop through leagues and insert
		$.each( leagues[languageCode], function(k, v) {
			if ( 'categories' == k )
				return;

			// if this is an object (probably is, loop within)
			if ( _.isArray(v.teams) ) {
				$option = $('<input>', {
					type: 'checkbox',
					val: 'league:' + v.id,
					'data-league-id': v.id,
					// name: name,
					checked: _.contains(savedLeagues, 'league:' + v.id)
				});

				$leaguesSelectbox.append( $('<label>', {
					text: k,
					class: 'nm-opt-group'
				}).prepend($option) );

				// append the language feed selector
				$leaguesSelectbox.append( $('<label>', {
					text: NM.strings.leagueFeed,
					class: 'nm-opt-option nm-opt-option-leaguefeed'
				}).prepend( $('<input>', {
					type: 'checkbox',
					val: 'league:' + v.id,
					'data-league-id': v.id,
					name: name,
					checked: _.contains(savedLeagues, 'league:' + v.id)
				}) ) );

				// loop over teams
				$.each( v.teams, function(index, value) {
					$option = $('<input>', {
						type: 'checkbox',
						val: 'team:' + value.id,
						name: name,
						text: '' + value.name,
						checked: _.contains(savedLeagues, 'team:' + value.id)
					});

					$leaguesSelectbox.append( $('<label>', {
						text: '' + value.name,
						class: 'nm-opt-option',
						'data-nm-league-id': v.id
					}).prepend($option) );
				});

			} else {
				$leaguesSelectbox.append(
					$('<option>', {
						val: k,
						text: k
					})
				);
			}

		});
	};

	NM.init();
	jQuery( '#nm-tabs' ).find( 'a' ).click( function() {
				jQuery( '#nm-tabs' ).find( 'a' ).removeClass( 'nav-tab-active' );
				jQuery( '.nmtab' ).removeClass( 'active' );

				var id = jQuery( this ).attr( 'id' ).replace( '-tab', '' );
				jQuery( '#' + id ).addClass( 'active' );
				jQuery( this ).addClass( 'nav-tab-active' );
			}
		);
	
};
 
jQuery( nm_main );
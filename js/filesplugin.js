const black_list_exts = [
  '.zip',
  '.exe',
  '.dll'
];

(function() {

  $.fn.hasAttr = function(name) {  
    return this.attr(name) !== undefined;
  };

  //Now i create the popup container
  const FPP_popup = document.createElement('div');
  $(FPP_popup).addClass('nextembed_popup bubble');

  // Crea il bottone di chiusura
  const closeButton = document.createElement('div');
  $(closeButton).addClass('nextembed_close_btn');

  // Crea e aggiungi lo span al bottone di chiusura
  const closeButtonIcon = document.createElement('span');
  $(closeButtonIcon).addClass('icon-close');
  $(closeButton).append(closeButtonIcon);

  // Collega l'evento click al bottone di chiusura
  $(closeButton).on('click', nextembed_close_popup);

  // Crea e aggiungi l'area di testo
  const textArea = document.createElement('textarea');

  // Aggiungi gli elementi al popup
  $(FPP_popup).append(closeButton, textArea);

  // Aggiungi il popup al body
  $('body').append(FPP_popup);

  // Aggiungi il popup al body
  $('body').append(FPP_popup);

  console.log('Nextembed loaded!');

  setInterval(nextembed_checks, 1000);
  
}) ();

function nextembed_checks() {
  $(".files-list__row").each(function( index ) {
    if(!$(this).hasAttr('nextembed') && $(this).find('.folder-icon').length <= 0 && !black_list_exts.includes($(this).find('.files-list__row-name-ext').html())) {

      // Test fatti:
      //Mi assicuro che non sia già sttato elaborato
      //Mi assicuro che sia un file     
      //Mi assicuro che non si tratti di un file con estensione in black-list
      

      // Set the row as processed
      $(this).attr('nextembed', 1);

      // Create the button element
      const button = document.createElement('button');
      button.setAttribute('aria-label', '');
      button.setAttribute('type', 'button');
      button.setAttribute('title', 'Get embed code');
      button.className = 'button-vue button-vue--size-normal button-vue--icon-only button-vue--vue-tertiary action-item action-item--single nextembed-btn';

      // Append the span wrapper to the button
      $(button).append(`
        <span data-v-44398b0c="" class="button-vue__wrapper">
          <span data-v-44398b0c="" aria-hidden="true" class="button-vue__icon">
            <span data-v-2d0a4d76="" data-v-8bb9b100="" role="img" aria-hidden="true" class="icon-vue" data-v-44398b0c="" style="--icon-size: 20px;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M8.59,16.59L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.59ZM16.41,16.59L11.83,12L16.41,7.41L15,6L9,12L15,18L16.41,16.59Z"></path>
              </svg>
            </span>
          </span>
        </span>
      `);      

      // Append the button
      $(this).find('.action-items').append(button);

      // Attach the click event
      const fileId = $(this).attr('data-cy-files-list-row-fileid');
      button.addEventListener('click', function() {
        nextembed_generate_embed_code(fileId);
      });

    }
  });  
}

function nextembed_generate_embed_code(fileId) {
  const filename = $(`tr[data-cy-files-list-row-fileid="${fileId}"]`).find('.files-list__row-name-').html();
  console.log("Generated embed code for:", filename);
  createPublicLink(fileId, function(token) {    
    //Mostro già il popup
    $('.nextembed_popup')
      .css({ top: `10%`, left: `${($(window).width()/2 -  $('.nextembed_popup').width()/2)}px`, display: 'block'})
      .find('textarea')                                                                   //OC.generateUrl('apps/nextembed/nextembed.php?token=')
      .text(`<iframe  width="100%" height="100%" src="${OC.getProtocol()}://${OC.getHostName()}/apps/nextembed/nextembed.php?token=${token}" title="${filename}"></iframe>`);
  });
}

function exist(elm) {
  if(elm !== null && elm !== undefined)
    return true;
  return false;
}


function createPublicLink(fileId, callback) {
  $.ajax({
    url: OC.generateUrl(`apps/nextembed/api/0.1/tokenizeFile/${fileId}`),
    method: 'GET',     
    dataType: 'json',
    headers: {
      'OCS-APIREQUEST': true,
      'X-Requested-With': 'XMLHttpRequest'
    },
    success: function(response) {
      if (response.fileToken) {
        callback(response.fileToken);
      } else {
        console.error('Failed to create public link:', response);
      }
    },
    error: function(xhr, status, error) {
      console.error('Error creating public link:', error);
    }
  });
}

function nextembed_close_popup() {
  $('.nextembed_popup').hide();
}
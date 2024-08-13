(function() {
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

  //Nextcloud get current user username
  const FPP_username = OC.getCurrentUser().uid;  
  

  OCA.Files.fileActions.registerAction({
    name: 'nextembed_popup',
    render: (actionSpec, isDefault, context) => {      
        //Preparo un array di estensioni proibite
        const FPP_prohibited_extensions = ['md'];

        //Controllo che l'estensione del file non sia tra quelle proibite
        const is_proibited = FPP_prohibited_extensions.includes(context.$file.attr('data-file').split('.').pop());

        //Analizzo file per file per vedere se hanno una preview da mostare oppure se è un pdf
        const has_preview = context.$file.data('has-preview');
        const is_pdf = context.$file.data('mime').includes('application/pdf');

        if ((has_preview || is_pdf) && !is_proibited)            
          context.$file.find('a.name>.thumbnail-wrapper').addClass('nextembed_popup-trigger').attr('fpp-id', context.$file.data('id'));
        return null
    },
    mime: 'all',
    order: -140,
    type: OCA.Files.FileActions.TYPE_INLINE,
    permissions: OC.PERMISSION_READ,
    actionHandler: null,
  });

  // Register the "Get embed code" action
  OCA.Files.fileActions.registerAction({
    name: 'getEmbedCode', // Unique name for the action
    displayName: t('getEmbedCode', 'get Embed Code'),
    iconClass: 'icon-link',
    render: (actionSpec, isDefault, context) => {
      // Create the menu item
      const menuItem = $('<a/>')
        .addClass('action')
        .text('Get embed code')
        .on('click', () => {
          handleEmbedCode(context);
        });

      return menuItem;
    },
    mime: 'all', // This action applies to all file types
    order: -130, // Position in the menu (adjust as needed)
    type: OCA.Files.FileActions.TYPE_CONTEXTMENU, // Add it to the context menu
    permissions: OC.PERMISSION_READ, // Required permissions
    actionHandler: function(filename, context) {
      // Replace this with your actual implementation
      const fileId = context.$file.data('id');

      createPublicLink(fileId, function(token) {
        
        //Mostro già il popup
        $('.nextembed_popup')
        .css({ top: `10%`, left: `${($(window).width()/2 -  $('.nextembed_popup').width()/2)}px`, display: 'block'})
        .find('textarea')                                                                   //OC.generateUrl('apps/nextembed/nextembed.php?token=')
        .text(`<iframe  width="100%" height="100%" src="${OC.getProtocol()}://${OC.getHostName()}/apps/nextembed/nextembed.php?token=${token}" title="${filename}"></iframe>`);
      });
        
      
    },
  });

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
  
})();

function exist(elm) {
  if(elm !== null && elm !== undefined)
    return true;
  return false;
}

function nextembed_close_popup() {
  $('.nextembed_popup').hide().find('textarea').val('');
}
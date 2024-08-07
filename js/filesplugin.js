(function() {
  //Now i create the popup container
  const FPP_popup = document.createElement('div');
  $(FPP_popup).addClass('fpp-pupup bubble').append('<div id="fpp-preview-loading" class="icon-loading dark creatable"></div>');
  $('body').append(FPP_popup);

  //Nextcloud get current user username
  const FPP_username = OC.getCurrentUser().uid;  
  

  OCA.Files.fileActions.registerAction({
    name: 'FPP_popuppreviewer',
    render: (actionSpec, isDefault, context) => {      
        //Preparo un array di estensioni proibite
        const FPP_prohibited_extensions = ['md'];

        //Controllo che l'estensione del file non sia tra quelle proibite
        const is_proibited = FPP_prohibited_extensions.includes(context.$file.attr('data-file').split('.').pop());

        //Analizzo file per file per vedere se hanno una preview da mostare oppure se è un pdf
        const has_preview = context.$file.data('has-preview');
        const is_pdf = context.$file.data('mime').includes('application/pdf');

        if ((has_preview || is_pdf) && !is_proibited)            
          context.$file.find('a.name>.thumbnail-wrapper').addClass('fpp-pupup-trigger').attr('fpp-id', context.$file.data('id'));
        return null
    },
    mime: 'all',
    order: -140,
    type: OCA.Files.FileActions.TYPE_INLINE,
    permissions: OC.PERMISSION_READ,
    actionHandler: null,
  });

  //jquery on hover function with event argument
  let fpp_mover_timer = null;
  $(document).on('mouseenter', '.fpp-pupup-trigger', function(event) {
    let elm = this;
    let fpp_pos_Y = event.pageY;
    const FPP_currentDir = OCA.Files.App.fileList.getCurrentDirectory();	 

    //Controllo che la finestra aperta non esca fuori dalla pagina
    if(fpp_pos_Y + 390 >= $(window).height()) {
      fpp_pos_Y = fpp_pos_Y - 390;
      $('.fpp-pupup').addClass('fpp-anti-bauble');
    }else if(fpp_pos_Y - 370 <= 0)
      $('.fpp-pupup').removeClass('fpp-anti-bauble');
    else
      $('.fpp-pupup').removeClass('fpp-anti-bauble');
    

    //Controllo che il mouse sia sopra il file
    if($(event.target).offset().top >= event.pageY) 
      fpp_pos_Y = fpp_pos_Y + 45;
    else if(event.pageY >= ($(event.target).offset().top + 45))
      fpp_pos_Y = fpp_pos_Y - 25;
      
    //Mostro già il popup
    $('.fpp-pupup').css({ top: `${fpp_pos_Y}px`, left: `${event.pageX}px`, display: 'block'});

    //Attendo 1.5 sec prima di procedere col  caricare l'immagine
    fpp_mover_timer = setTimeout(function () {   
      let fpp_logo = document.createElement('img');

      //Controllo se il file è un pdf o un'immagine
      if($(elm).closest('tr').find('a.name>.nametext').text().split('.').pop() == 'pdf') {
        //Get file owner
        const FPP_file_owner = $(elm).closest('tr').attr('data-share-owner-id');
        console.log(FPP_file_owner);

        //jquery get request
        $.get(OC.generateUrl('apps/nextembed/api/0.1/pdfpreview?filename={filename}&path={path}&user={user}', {user: escape((exist(FPP_file_owner)?FPP_file_owner:FPP_username)), filename: escape($(elm).closest('tr').find('a.name>.nametext').text()), path: escape(FPP_currentDir)}), function(data) {
          if(data.preview !== null && data.preview !== undefined)
            fpp_logo.src = data.preview;
          else
            console.log(data.error);
        });        
      }else
        fpp_logo.src = OC.generateUrl(`core/preview?fileId=${$(elm).attr('fpp-id')}&c=e1c03cfac2e0914179c3ff120d226dcd&x=330&y=390&forceIcon=0&a=0`);
      
      fpp_logo.onload = function () {
        //Controllo see si tratta di un'immaginee grande allora diventa 'contain' altrimenti 'cover'
        // let fpp_bg_size = 'contain';
        // if(this.width > 330 || this.height > 390)
        //   fpp_bg_size = 'cover';

        $('.fpp-pupup').css({ 'background-image': `url('${this.src}')`});
        $('.fpp-pupup').find('#fpp-preview-loading').hide();      
      };
    }, 1500);
  });

  //jquery on leave function with event argument
  $(document).on('mouseleave', '.fpp-pupup-trigger', function(event) {
    if(fpp_mover_timer !== null)
      clearTimeout(fpp_mover_timer);
    $('.fpp-pupup').css({ top: '0px', left: '0px', display: 'none', 'background-image': '' });
    $('#fpp-preview-loading').show();
  });

})();

function exist(elm) {
  if(elm !== null && elm !== undefined)
    return true;
  return false;
}
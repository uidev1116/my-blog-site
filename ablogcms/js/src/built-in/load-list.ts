import { loadClosureFactory, loadClosureFactoryCss, assignLoadClosure } from '../lib/loader';

type LoadClosureCollection = {
  Dispatch: any; // eslint-disable-line @typescript-eslint/no-explicit-any
  Library: any; // eslint-disable-line @typescript-eslint/no-explicit-any
};

export default (path: string): void => {
  //------------
  // collection
  const loadClosureCollection: Partial<LoadClosureCollection> = {};

  loadClosureCollection.Dispatch = {};
  loadClosureCollection.Dispatch._static2dynamic = loadClosureFactory(`${path}dispatch/_static2dynamic.js`);
  loadClosureCollection.Dispatch._observefilesize = loadClosureFactory(`${path}dispatch/_observefilesize.js`);
  loadClosureCollection.Dispatch._validate = loadClosureFactory(`${path}dispatch/_validate.js`);
  loadClosureCollection.Dispatch._revision = loadClosureFactory(`${path}dispatch/_revision.js`);
  loadClosureCollection.Dispatch._imgresize = loadClosureFactory(`${path}dispatch/_imgresize.js`);
  loadClosureCollection.Dispatch.Layout = loadClosureFactory(`${path}dispatch/layout.js`);
  loadClosureCollection.Dispatch.ModuleDialog = loadClosureFactory(`${path}dispatch/moduleDialog.js`);
  loadClosureCollection.Dispatch.Postinclude = loadClosureFactory(`${path}dispatch/postinclude.js`);
  loadClosureCollection.Dispatch.Postinclude._postinclude = loadClosureFactory(
    `${path}dispatch/postinclude/_postinclude.js`
  );
  loadClosureCollection.Dispatch.Linkmatchlocation = loadClosureFactory(`${path}dispatch/linkmatchlocation.js`);
  loadClosureCollection.Dispatch.Admin = loadClosureFactory(`${path}dispatch/admin.js`);
  loadClosureCollection.Dispatch.Admin.Configunit = loadClosureFactory(`${path}dispatch/admin/configunit.js`);
  loadClosureCollection.Dispatch.Edit = loadClosureFactory(`${path}dispatch/edit.js`);
  loadClosureCollection.Dispatch.Edit._change = loadClosureFactory(`${path}dispatch/edit/_change.js`);
  loadClosureCollection.Dispatch.Edit._item = loadClosureFactory(`${path}dispatch/edit/_item.js`);
  loadClosureCollection.Dispatch.Edit._tagassist = loadClosureFactory(`${path}dispatch/edit/_tagassist.js`);
  loadClosureCollection.Dispatch.Edit._inplace = loadClosureFactory(`${path}dispatch/edit/_inplace.js`);
  loadClosureCollection.Dispatch.Edit._direct = loadClosureFactory(`${path}dispatch/edit/_direct.js`);
  loadClosureCollection.Dispatch.Edit._experimental = loadClosureFactory(`${path}dispatch/edit/_experimental.js`);
  loadClosureCollection.Dispatch.Edit.map = loadClosureFactory(`${path}dispatch/edit/map.js`);
  loadClosureCollection.Dispatch.highslide = loadClosureFactory(`${path}dispatch/highslide.js`);
  loadClosureCollection.Dispatch.Dialog = loadClosureFactory(`${path}dispatch/dialog.js`);
  loadClosureCollection.Library = {};
  loadClosureCollection.Library.validator = loadClosureFactory(`${path}library/validator.js`);
  loadClosureCollection.Library.highslide = loadClosureFactory(
    `${path}library/highslide/highslide.js`,
    '',
    () => {
      // @ts-expect-error global variable
      global.hs = undefined;
    },
    () => {
      ACMS.Dispatch._highslideInit();
      loadClosureFactoryCss(`${ACMS.Config.jsRoot}library/highslide/highslide.css`);
    }
  );
  loadClosureCollection.Library.swfobject = loadClosureFactory(`${path}library/swfobject/swfobject.js`, '', () => {
    // @ts-expect-error global variable
    global.hs = undefined;
  });
  loadClosureCollection.Library.Jquery = {};
  loadClosureCollection.Library.Jquery.biggerlink = loadClosureFactory(
    `${path}library/jquery/jquery.biggerlink.min.js`
  );
  loadClosureCollection.Library.Jquery.autoheight = loadClosureFactory(`${path}library/jquery/jqueryAutoHeight.js`);
  loadClosureCollection.Library.Jquery.selection = loadClosureFactory(`${path}library/jquery/jquery.selection.js`);
  loadClosureCollection.Library.Jquery.prettyphoto = loadClosureFactory(
    `${path}library/jquery/prettyphoto/jquery.prettyPhoto.js`,
    '',
    () => {
      loadClosureFactoryCss(`${ACMS.Config.jsRoot}library/jquery/prettyphoto/css/prettyPhoto.css`);
    }
  );
  loadClosureCollection.Library.Jquery.bxslider = loadClosureFactory(
    `${path}library/jquery/bxslider/jquery.bxslider.min.js`,
    '',
    () => {
      loadClosureFactoryCss(`${ACMS.Config.jsRoot}library/jquery/bxslider/jquery.bxslider.css`);
    }
  );
  loadClosureCollection.Library.googleCodePrettify = loadClosureFactory(
    `${path}library/google-code-prettify/prettify.js`,
    '',
    () => {
      loadClosureFactoryCss(
        `${ACMS.Config.jsRoot}library/google-code-prettify/styles/${ACMS.Config.googleCodePrettifyTheme}.css`
      );
    },
    () => {
      ACMS.Library.googleCodePrettifyPost();
    }
  );
  loadClosureFactoryCss(`${ACMS.Config.jsRoot}library/jquery/ui_1.13.2/jquery-ui.min.css`);

  ACMS.Load = loadClosureCollection;

  //--------
  // define
  assignLoadClosure('ACMS.Dispatch._tagassist', loadClosureCollection.Dispatch._tagassist);
  assignLoadClosure('ACMS.Dispatch._static2dynamic', loadClosureCollection.Dispatch._static2dynamic);
  assignLoadClosure('ACMS.Dispatch._observefilesize', loadClosureCollection.Dispatch._observefilesize);
  assignLoadClosure('ACMS.Dispatch._validate', loadClosureCollection.Dispatch._validate);
  assignLoadClosure('ACMS.Dispatch._revision', loadClosureCollection.Dispatch._revision);
  assignLoadClosure('ACMS.Dispatch._imgresize', loadClosureCollection.Dispatch._imgresize);
  assignLoadClosure('ACMS.Dispatch._highslideInit', loadClosureCollection.Dispatch.highslide);
  assignLoadClosure('ACMS.Dispatch.highslide', loadClosureCollection.Dispatch.highslide);
  assignLoadClosure('ACMS.Dispatch.Layout', loadClosureCollection.Dispatch.Layout);
  assignLoadClosure('ACMS.Dispatch.ModuleDialog', loadClosureCollection.Dispatch.ModuleDialog);
  assignLoadClosure('ACMS.Dispatch.Dialog', loadClosureCollection.Dispatch.Dialog);
  assignLoadClosure('ACMS.Dispatch.Postinclude.ready', loadClosureCollection.Dispatch.Postinclude);
  assignLoadClosure('ACMS.Dispatch.Postinclude.bottom', loadClosureCollection.Dispatch.Postinclude);
  assignLoadClosure('ACMS.Dispatch.Postinclude.interval', loadClosureCollection.Dispatch.Postinclude);
  assignLoadClosure('ACMS.Dispatch.Postinclude.submit', loadClosureCollection.Dispatch.Postinclude);
  assignLoadClosure('ACMS.Dispatch.Postinclude._postinclude', loadClosureCollection.Dispatch.Postinclude._postinclude);
  assignLoadClosure('ACMS.Dispatch.Linkmatchlocation.part', loadClosureCollection.Dispatch.Linkmatchlocation);
  assignLoadClosure('ACMS.Dispatch.Linkmatchlocation.full', loadClosureCollection.Dispatch.Linkmatchlocation);
  assignLoadClosure('ACMS.Dispatch.Linkmatchlocation.blog', loadClosureCollection.Dispatch.Linkmatchlocation);
  assignLoadClosure('ACMS.Dispatch.Linkmatchlocation.category', loadClosureCollection.Dispatch.Linkmatchlocation);
  assignLoadClosure('ACMS.Dispatch.Linkmatchlocation.entry', loadClosureCollection.Dispatch.Linkmatchlocation);
  assignLoadClosure('ACMS.Dispatch.Admin', loadClosureCollection.Dispatch.Admin);
  assignLoadClosure('ACMS.Dispatch.Admin.Configunit', loadClosureCollection.Dispatch.Admin.Configunit);
  assignLoadClosure('ACMS.Dispatch.Admin.Configunit._add', loadClosureCollection.Dispatch.Admin.Configunit);
  assignLoadClosure('ACMS.Dispatch.Admin.Configunit.remove', loadClosureCollection.Dispatch.Admin.Configunit);
  assignLoadClosure('ACMS.Dispatch.Edit', loadClosureCollection.Dispatch.Edit);
  assignLoadClosure('ACMS.Dispatch.Edit._add', loadClosureCollection.Dispatch.Edit._add);
  assignLoadClosure('ACMS.Dispatch.Edit._change', loadClosureCollection.Dispatch.Edit._change);
  assignLoadClosure('ACMS.Dispatch.Edit._item', loadClosureCollection.Dispatch.Edit._item);
  assignLoadClosure('ACMS.Dispatch.Edit._tagassist', loadClosureCollection.Dispatch.Edit._tagassist);
  assignLoadClosure('ACMS.Dispatch.Edit._inplace', loadClosureCollection.Dispatch.Edit._inplace);
  assignLoadClosure('ACMS.Dispatch.Edit._direct', loadClosureCollection.Dispatch.Edit._direct);
  assignLoadClosure('ACMS.Dispatch.Edit._experimental', loadClosureCollection.Dispatch.Edit._experimental);
  assignLoadClosure('ACMS.Dispatch.Edit.updatetime', loadClosureCollection.Dispatch.Edit);
  assignLoadClosure('ACMS.Dispatch.Edit.map', loadClosureCollection.Dispatch.Edit.map);
  assignLoadClosure('ACMS.Library.Validator.isFunction', loadClosureCollection.Library.validator);
  assignLoadClosure('ACMS.Library.googleCodePrettify', loadClosureCollection.Library.googleCodePrettify);
  assignLoadClosure('hs.expand', loadClosureCollection.Library.highslide, true);
  assignLoadClosure('swfobject.embedSWF', loadClosureCollection.Library.swfobject, true);
  assignLoadClosure('jQuery.fn.biggerlink', loadClosureCollection.Library.Jquery.biggerlink);
  assignLoadClosure('jQuery.fn.bxSlider', loadClosureCollection.Library.Jquery.bxslider);
  assignLoadClosure('jQuery.fn.autoheight', loadClosureCollection.Library.Jquery.autoheight);
  assignLoadClosure('jQuery.fn.selection', loadClosureCollection.Library.Jquery.selection);
  assignLoadClosure('jQuery.fn.prettyPhoto', loadClosureCollection.Library.Jquery.prettyphoto);
};

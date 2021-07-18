import Util from './util';

export default class ResizeImage {
  /**
   * Constructor
   *
   * @param elm
   */
  constructor(elm) {
    this.elm = elm;
    this.dropAreaMark = '.js-drop_area';
    this.inputMark = ACMS.Config.resizeImageInputMark;
    this.previewMark = ACMS.Config.resizeImagePreviewMark;
    this.targetMark = ACMS.Config.resizeImageTargetMark;
    this.targetMarkCF = ACMS.Config.resizeImageTargetMarkCF;
    this.dropSelect = false;
    this.dragging = 0;
    this.previewOnly = ACMS.Config.resizeImage !== 'on';
    this.util = new Util();
  }

  /**
   * リサイズ処理の初期化
   */
  resize() {
    const targetAry = this.elm.querySelectorAll(this.targetMark);

    if (targetAry.length > 0) {
      [].forEach.call(targetAry, (input) => {
        this.exec(input);
      });
    } else if (1
      && this.elm.classList.contains(this.targetMarkCF.substr(1))
      && !this.elm.classList.contains('resizeImage')
    ) {
      this.elm.classList.add('resizeImage');
      this.exec(this.elm);
    }
  }

  /**
   * targetに対してリサイズ処理イベントを登録
   *
   * @param target
   */
  exec(target) {
    const node = target.querySelector(this.previewMark);
    if (node !== null) {
      this.previewBox = node.cloneNode(true);
      target.querySelector(this.previewMark).insertAdjacentHTML('afterend', '<div class="js-img_resize_preview_location" />');
      this.listener(target);
    }
  }

  /**
   * 画像のファイル選択イベントを登録
   *
   * @param target
   */
  listener(target) {
    const dropArea = target.querySelector(this.dropAreaMark);
    const interval = 1500;
    let lastTime = new Date().getTime() - interval;

    this.dragging = 0;
    if (dropArea && window.File && window.FileReader && !this.previewOnly) {
      this.banDrag(target);

      target.querySelector('img').getAttribute('src');

      // ドロップできることを表示
      if (!target.querySelector('img').getAttribute('src')) {
        dropArea.classList.add('drag-n-drop-hover');
        setTimeout(() => {
          const area = dropArea.querySelector('.acms-admin-drop-area');
          $(area).fadeOut(200, () => {
            dropArea.classList.remove('drag-n-drop-hover');
            dropArea.querySelector('.acms-admin-drop-area').style.display = '';
          });
        }, 800);
      }
      // ドロップ時のアクションを設定
      dropArea.addEventListener('drop', (event) => {
        event.stopPropagation();
        event.preventDefault();
        this.dragging = 0;
        this.dropSelect = true;
        dropArea.classList.remove('drag-n-drop-hover');

        const files = event.dataTransfer.files;
        let gif = false;

        for (let i = 0; i < files.length; i++) {
          const file = files[i];
          if (file.type === 'image/gif') {
            gif = true;
            break;
          }
        }
        if (gif) {
          if (!window.confirm(ACMS.i18n('drop_select_gif_image.alert'))) { // eslint-disable-line no-alert, no-console
            return false;
          }
        }
        this.readFiles(event.dataTransfer.files, target);
        return false;
      }, false);

      // ドロップエリアにいる間
      dropArea.addEventListener('dragover', (event) => {
        event.stopPropagation();
        event.preventDefault();
        dropArea.classList.add('drag-n-drop-hover');
        return false;
      }, false);

      // ドロップエリアに入った時
      dropArea.addEventListener('dragenter', (event) => {
        event.stopPropagation();
        event.preventDefault();
        this.dragging++;
        dropArea.classList.add('drag-n-drop-hover');
        return false;
      }, false);

      // ドロップエリアから出て行った時
      dropArea.addEventListener('dragleave', (event) => {
        event.stopPropagation();
        event.preventDefault();
        this.dragging--;
        if (this.dragging === 0) {
          dropArea.classList.remove('drag-n-drop-hover');
        }
        return false;
      }, false);
    } else {
      // ブラウザが対応していない場合の処理
    }

    // フォーム入力よりファイルが選択された
    $(this.inputMark, target).on('change', (event) => {
      if ((lastTime + interval) <= new Date().getTime()) {
        lastTime = new Date().getTime();
        this.readFiles(event.target.files, target);
      }
    });
  }

  /**
   * 画像ファイルを読み込み
   *
   * @param files
   * @param target
   * @return {boolean}
   */
  readFiles(files, target) {
    const sizeSelect = target.querySelectorAll('[name^=image_size_]');
    const bannerSize = document.querySelectorAll('.js-banner_size_large');
    const bannerSizeCriterion = document.querySelector('.js-banner_size_large_criterion');
    const rawSize = sizeSelect.length > 0 && sizeSelect[0].value.length < 2;

    // 多言語対応ユニット
    if (sizeSelect.length > 1) {
      return false;
    }

    // バナーモジュール
    if (bannerSize.length >= 1 && bannerSize.value) {
      ACMS.Config.lgImg = `${bannerSizeCriterion.value}:${bannerSize[0].value}`;
    }
    [].forEach.call(target.querySelectorAll(this.previewMark), (item) => {
      item.parentNode.removeChild(item);
    });
    [].forEach.call(target.querySelectorAll('.js-img_resize_data'), (item) => {
      item.parentNode.removeChild(item);
    });
    [].forEach.call(target.querySelectorAll('.js-img_exif_data'), (item) => {
      item.parentNode.removeChild(item);
    });

    const lgImageSize = ACMS.Config.lgImg;
    const lgImgAry = lgImageSize.split(':');
    const multi = files.length > 1;
    const lgImgSide = lgImgAry[0];
    let lgImgSize = lgImgAry[1];

    if (rawSize) {
      lgImgSize = 999999999;
    }
    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      if (!file) continue;
      this.util.getDataUrlFromFile(file, lgImgSide, lgImgSize).then((data) => {
        const dataUrl = data.dataUrl;
        let resize = data.resize;

        if (rawSize) {
          resize = false;
        }
        if (multi) {
          resize = true;
        }
        if (!this.dropSelect && file.type === 'image/gif') {
          resize = false;
        }
        if (this.previewOnly) {
          resize = false;
        }
        import(/* webpackChunkName: "exif-js" */'exif-js').then(({ default: Exif }) => {
          Exif.getData(file, () => {
            const exif = Exif.getAllTags(file);
            this.set(target, dataUrl, resize || this.dropSelect, exif, multi);
          });
        });
      });
    }
  }

  /**
   * 画像ドラッグでの失敗を抑制
   *
   * @param target
   */
  banDrag(target) {
    [].forEach.call(target.querySelectorAll('img'), (item) => {
      item.addEventListener('mousedown', (event) => {
        event.preventDefault();
      });
      item.addEventListener('mouseup', (event) => {
        event.preventDefault();
      });
    });
  }

  /**
   * 分数を約分
   *
   * @param numerator
   * @param denominator
   */
  reduce(numerator, denominator) {
    if (numerator > denominator) {
      return Math.floor(numerator / denominator);
    }
    const numerator0 = numerator;
    const denominator0 = denominator;
    let c;
    while (1) { // eslint-disable-line no-constant-condition
      c = numerator % denominator;
      if (c === 0) break;
      denominator = numerator;
      numerator = c;
    }
    return `${Math.floor(numerator0 / numerator)}/${Math.floor(denominator0 / numerator)}`;
  }

  /**
   * リサイズした画像データをdomとして追加
   *
   * @param target
   * @param dataUrl
   * @param resize
   * @param exif
   * @param multi
   */
  set(target, dataUrl, resize, exif, multi) {
    //------
    // exif
    [].forEach.call(target.querySelectorAll('.js-img_exif_add'), (item) => {
      item.style.display = 'none';
    });
    let checkField = true;
    ACMS.Config.exif.requireField.forEach((item) => {
      if (!exif[item]) {
        checkField = false;
      }
    });

    if (1
      && ACMS.Config.exif.captionEnable === 'on'
      && checkField
      && ACMS.Config.exif.requireField instanceof Array
    ) {
      if (checkField) {
        if (exif.ExposureTime && exif.ExposureTime.numerator && exif.ExposureTime.denominator) {
          exif.ExposureTime = this.reduce(exif.ExposureTime.numerator, exif.ExposureTime.denominator);
        }
        if (exif.DateTimeOriginal) {
          exif.DateTimeOriginal = exif.DateTimeOriginal.replace(/(\d{4}):(\d{2}):(\d{2})\s(\d{2}):(\d{2}):(\d{2})/g, '$1-$2-$3 $4:$5:$6');
        }
        if (!multi) {
          const tpl = _.template(ACMS.Config.exif.captionFormat);
          const dataExif = target.querySelector('.js-img_exif_add');
          if (dataExif) {
            dataExif.setAttribute('data-exif', tpl(exif));
            dataExif.style.display = '';
          }
        }
      }
    }
    if (ACMS.Config.exif.saveData === 'on' && checkField) {
      const name = target.querySelector(this.inputMark).getAttribute('name');

      if (name !== name.replace('file', 'exif')) {
        const tpl2 = _.template(ACMS.Config.exif.dataFormat);
        const exifData = document.createElement('input');

        exifData.classList.add('js-img_exif_data');
        exifData.setAttribute('type', 'hidden');
        exifData.setAttribute('name', target.querySelector(this.inputMark).getAttribute('name').replace('file', 'exif'));
        exifData.value = tpl2(exif);

        target.querySelector(this.inputMark).insertAdjacentHTML('afterend', exifData.outerHTML);
      }
    }

    if (!this.previewBox) {
      this.previewBox = target.querySelector(this.previewMark).cloneNode(true);
      [].forEach.call(target.querySelectorAll(this.previewMark), (item) => {
        item.parentNode.removeChild(item);
      });
      [].forEach.call(target.querySelectorAll('.js-img_resize_data'), (item) => {
        item.parentNode.removeChild(item);
      });
    }

    // preview
    const clone = this.previewBox.cloneNode(true);
    clone.style.display = '';
    clone.setAttribute('src', dataUrl);
    target.querySelector('.js-img_resize_preview_location').insertAdjacentHTML('beforebegin', clone.outerHTML);
    [].forEach.call(target.querySelectorAll('.js-img_resize_preview_old'), (item) => {
      item.parentNode.removeChild(item);
    });
    const imgDataUrl = target.querySelector('.js-img_data_url');
    if (imgDataUrl) {
      imgDataUrl.setAttribute('data-src', dataUrl);
    }

    // ban drag
    this.banDrag(target);

    const input = target.querySelector(this.inputMark);

    // insert data
    if (resize) {
      $(this.inputMark, target).replaceWith($(this.inputMark, target).clone(true));
      if ($(this.inputMark, target).val() !== '') {
        $(this.inputMark, target).val('');
      }
    } else {
      dataUrl = '';
    }

    const dataForm = document.createElement('input');
    dataForm.classList.add('js-img_resize_data');
    dataForm.setAttribute('type', 'hidden');
    dataForm.setAttribute('accept', 'image/*');
    dataForm.setAttribute('type', 'hidden');
    dataForm.setAttribute('name', input.getAttribute('name'));
    dataForm.value = dataUrl;

    target.querySelector(this.inputMark).insertAdjacentHTML('afterend', dataForm.outerHTML);
  }
}

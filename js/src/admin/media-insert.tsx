import React from 'react';
import { render } from 'react-dom';
import MediaUnit from '../components/media-unit';
import { MediaItem } from '../types/media';
import { addClass } from '../lib/dom';

export default (ctx: HTMLElement) => {
  const units = ctx.querySelectorAll('.js-media-unit');
  [].forEach.call(units, (unit: HTMLElement) => {
    if (!unit) {
      return;
    }
    addClass(unit, 'done');
    const id = unit.dataset.id;
    const { primaryImageId, rootDir, mediaSizes, bid, diff, multiUpload,
      mediaDir, active, enlarged, primary, thumbnail, type, path, pdf, pdfIcon,
      caption, text, alt, mid, link, landscape, lang, name, nolink, overrideLink, overrideAlt, overrideCaption
    } = unit.dataset;
    const thumbnailPath = type === 'file' ? `${rootDir}${thumbnail}` : `${mediaDir}${thumbnail}`;
    const item = {
      media_caption: caption,
      media_text: text,
      media_alt: alt,
      media_id: mid,
      media_link: link,
      media_landscape: landscape,
      media_thumbnail: thumbnailPath,
      media_type: type,
      media_pdf: pdf,
      media_title: name
    } as MediaItem;
    let mediaSizesFiltered = [];
    if (mediaSizes) {
      mediaSizesFiltered = JSON.parse(mediaSizes).filter((item: object) => {
        if (Object.keys(item).length === 0) {
          return false;
        }
        return true;
      })
    }
    render(<MediaUnit
      items={[item]}
      id={id}
      primaryImageId={primaryImageId}
      mediaSizes={mediaSizesFiltered}
      mediaDir={mediaDir}
      rootDir={rootDir}
      bid={bid}
      diff={diff}
      active={active}
      path={path}
      lang={lang}
      primary={primary as 'true' | 'false'}
      multiUpload={multiUpload === 'false' ? 'false' : 'true'}
      usePdfIcon={pdfIcon as 'yes' | 'no'}
      enlarged={enlarged as 'true' | 'false'}
      hasLink={nolink as 'true' | 'false'}
      overrideLink={overrideLink}
      overrideAlt={overrideAlt}
      overrideCaption={overrideCaption}
    />,
    unit);
  });
};

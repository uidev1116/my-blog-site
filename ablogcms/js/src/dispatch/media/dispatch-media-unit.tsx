import { lazy, Suspense } from 'react';
import { render } from '../../utils/react';
import { MediaItem } from '../../features/media/types';
import { triggerEvent } from '../../utils';

export default function dispatchMediaUnit(context: Element | Document = document) {
  const elements = context.querySelectorAll<HTMLElement>('.js-media-unit');
  if (elements.length === 0) {
    return;
  }
  elements.forEach((element) => {
    element.classList.add('done');
    const {
      id = '',
      primaryImageId = '',
      rootDir = '',
      mediaSizes,
      bid = ACMS.Config.bid,
      diff = '',
      multiUpload,
      mediaDir = '',
      active = ACMS.Config.mediaLibrary,
      enlarged,
      primary,
      thumbnail,
      type,
      path = '',
      pdf,
      pdfIcon,
      caption,
      text,
      alt,
      mid,
      link,
      landscape,
      lang,
      name,
      nolink,
      overrideLink = '',
      overrideAlt = '',
      overrideCaption = '',
    } = element.dataset;
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
      media_title: name,
    } as MediaItem;
    let mediaSizesFiltered = [];
    if (mediaSizes) {
      mediaSizesFiltered = JSON.parse(mediaSizes).filter((obj: object) => {
        if (Object.keys(obj).length === 0) {
          return false;
        }
        return true;
      });
    }

    const MediaUnit = lazy(
      () => import(/* webpackChunkName: "media-unit" */ '../../features/media/components/media-unit/media-unit')
    );
    render(
      <Suspense fallback={null}>
        <MediaUnit
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
          onChange={(mediaItems) => {
            triggerEvent(element, 'acmsAdminMediaUnitChange', { bubbles: true, detail: { mediaItems } });
          }}
        />
      </Suspense>,
      element
    );
  });
}

import { lazy, Suspense } from 'react';
import { render } from '../../utils/react';

export default function dispatchMediaAdmin(context: Element | Document = document) {
  const elements = context.querySelectorAll<HTMLElement>(ACMS.Config.mediaAdminMark);
  if (elements.length === 0) {
    return;
  }
  const MediaAdmin = lazy(
    () => import(/* webpackChunkName: "media-admin" */ '../../features/media/components/media-admin/media-admin')
  );

  elements.forEach((element) => {
    render(
      <Suspense fallback={null}>
        <MediaAdmin />
      </Suspense>,
      element
    );
  });
}

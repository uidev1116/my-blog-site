import { lazy, Suspense } from 'react';
import { AdminCardLaneType, AdminCardType } from '../features/admin-menu/types';
import { render } from '../utils/react';

interface AdminMenuJson {
  lanes: Partial<AdminCardLaneType>[];
  cards: Partial<AdminCardType>[];
}

export default function dispatchAdminMenuEditor(context: Document | Element) {
  const element = context.querySelector<HTMLElement>(ACMS.Config.adminMenuEditMark);
  if (element === null) {
    return;
  }

  const AdminMenuEditor = lazy(
    () =>
      import(
        /* webpackChunkName: "admin-menu" */ '../features/admin-menu/components/admin-menu-editor/admin-menu-editor'
      )
  );
  const json = document.querySelector<HTMLScriptElement>('#admin-menu-json')?.innerHTML;

  if (!json) {
    throw new Error('Admin menu JSON is not found.');
  }
  const data: AdminMenuJson = JSON.parse(json);
  const cards = data.cards.filter((card) => card.id) as AdminCardType[];
  const lanes = data.lanes
    .filter((lane) => lane.id)
    .map((lane, i) => ({
      ...(i === 0 ? { ...lane, movable: false } : lane),
      cards: cards.filter((card) => card.laneId === lane.id),
    })) as AdminCardLaneType[];
  render(
    <Suspense fallback={null}>
      <AdminMenuEditor data={{ lanes }} />
    </Suspense>,
    element
  );
}

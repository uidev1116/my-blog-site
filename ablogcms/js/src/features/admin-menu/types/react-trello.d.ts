import { ReactNode, CSSProperties } from 'react';
import { AdminBoardDataType } from '.';

// Board コンポーネントのプロパティの型定義
// @appleple/react-trello がTypeScript非対応のため、型定義を追加
interface BoardProps {
  data: AdminBoardDataType;
  draggable?: boolean;
  editable?: boolean;
  style?: CSSProperties;
  canAddLanes?: boolean;
  addLaneTitle?: string;
  addCardTitle?: string;
  hideCardDeleteIcon?: boolean;
  newCardTemplate?: ReactNode;
  customLaneHeader?: ReactNode;
  customCardLayout?: boolean;
  handleLaneDragStart?: () => boolean;
  onDataChange?: (currentData: AdminBoardDataType) => void;
  children?: ReactNode;
}

declare module '@appleple/react-trello' {
  export default function Board(props: BoardProps): JSX.Element;
}

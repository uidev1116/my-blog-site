export interface AdminCardType {
  id: string;
  laneId: string;
  title: string;
  url: string;
  label: string;
  icon: string;
  admin?: boolean;
  index: number;
}

export interface AdminCardLaneType {
  id: string;
  title: string;
  index: number;
  movable?: boolean;
  cards: AdminCardType[];
}

export interface AdminBoardDataType {
  lanes: AdminCardLaneType[];
}

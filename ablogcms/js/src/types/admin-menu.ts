export interface AdminCardProps {
  id: string
  laneId: string
  title: string
  url: string
  label: string
  icon: string
  admin?: boolean
  index: number
  removeCard(id: string, laneId: string): void,
  onDelete(id: string, laneId: string): void
}

export interface AdminCardLane {
  id: string,
  title: string,
  index: number,
  cards: AdminCardProps[]
}
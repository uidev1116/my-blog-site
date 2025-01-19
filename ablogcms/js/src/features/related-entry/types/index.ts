export interface RelatedEntryType {
  id: number;
  title: string;
  categoryName: string;
  image: string;
  url: string;
}

export interface RelatedEntryOption extends RelatedEntryType {
  label: string;
  value: string;
}

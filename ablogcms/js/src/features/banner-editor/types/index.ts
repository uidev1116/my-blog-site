export interface BannerItem {
  id: string;
  media_banner_type: BannerType;
  media_banner_attr1?: string;
  media_banner_attr2?: string;
  media_banner_source: '';
  media_banner_status: 'true' | 'false';
  media_banner_target: 'true' | 'false';
  media_banner_preview: string;
  media_banner_datestart: string;
  media_banner_timestart: string;
  media_banner_dateend: string;
  media_banner_timeend: string;
  media_banner_mid: string;
  media_banner_alt: string;
  media_banner_landscape?: boolean;
  media_banner_link: string;
  media_banner_override_link: string;
  toggle: boolean;
}

export type SortableBannerItem = {
  id: string;
  data: BannerItem | null;
};

export type BannerType = 'image' | 'source';

import * as types from '../constants';
import { MediaAjaxConfig, MediaItem } from '../types';

export const fetchTagList = () => ({ type: types.FETCHTAGLIST });
export const fetchMediaList = (config?: MediaAjaxConfig) => ({ type: types.FETCHMEDIALIST, config });
export const setMediaList = (items: MediaItem[]) => ({ type: types.SETMEDIALIST, items });
export const setMediaConfig = (config: Partial<MediaAjaxConfig>) => ({ type: types.SETMEDIACONFIG, config });
export const setMediaLastPage = (page: number) => ({ type: types.SETMEDIALASTPAGE, page });
export const setMediaLargeSize = (largeSize: number) => ({ type: types.SETMEDIALARGESIZE, largeSize });
export const setFormToken = (formToken: string) => ({ type: types.SETFORMTOKEN, formToken });
export const setItem = (item: MediaItem | null) => ({ type: types.SETITEM, item });
export const updateMediaList = (item: MediaItem) => ({ type: types.UPDATEMEDIALIST, item });
export const setLabel = (label: string) => ({ type: types.SETLABEL, label });
export const setMediaArchives = (archives: string[]) => ({ type: types.SETMEDIAARCHIVES, archives });
export const setMediaTags = (tags: string[]) => ({ type: types.SETMEDIATAGS, tags });
export const setMediaTotal = (total: number) => ({ type: types.SETMEDIATOTAL, total });
export const setLoading = (loading: boolean) => ({ type: types.SETLOADING, loading });
export const setSelectedTags = (tags: string[]) => ({ type: types.SETSELECTEDTAGS, tags });
export const setMediaExtensions = (extensions: string[]) => ({ type: types.SETMEDIAEXTENSIONS, extensions });

import * as types from '../constants/media';
import { MediaItem } from '../types/media';

const initialState = {
  items: [],
  archives: [],
  tags: [],
  extensions: [],
  selectedTags: [],
  item: null,
  lastPage: 1,
  largeSize: 0,
  total: 0,
  config: {
    limit: 20,
    page: 1,
    order: 'upload_date-desc',
    keyword: '',
    tag: '',
    date: '',
  },
  formToken: '',
  label: '',
  upload: false,
  loading: false,
};

// eslint-disable-next-line default-param-last
export default (state = initialState, action) => {
  switch (action.type) {
    case types.SETMEDIALIST:
      const { items } = action;
      return { ...state, items };
    case types.SETMEDIALASTPAGE:
      return { ...state, lastPage: action.page };
    case types.SETMEDIACONFIG:
      return { ...state, config: { ...state.config, ...action.config } };
    case types.SETMEDIATAGS:
      return { ...state, tags: action.tags };
    case types.SETMEDIAARCHIVES:
      return { ...state, archives: action.archives };
    case types.SETITEM:
      return { ...state, item: action.item };
    case types.UPDATEMEDIALIST:
      const index = state.items.findIndex((item: MediaItem) => {
        if (item.media_id === action.item.media_id) {
          return true;
        }
        return false;
      });
      return {
        ...state,
        items: [...state.items.slice(0, index), action.item, ...state.items.slice(index + 1)],
      };
    case types.SETMEDIALARGESIZE:
      return { ...state, largeSize: action.largeSize };
    case types.SETFORMTOKEN:
      return { ...state, formToken: action.formToken };
    case types.SETUPLOAD:
      return { ...state, upload: action.upload };
    case types.SETLABEL:
      return { ...state, label: action.label };
    case types.SETMEDIATOTAL:
      return { ...state, total: action.total };
    case types.SETLOADING:
      return { ...state, loading: action.loading };
    case types.SETSELECTEDTAGS:
      return { ...state, selectedTags: action.tags };
    case types.SETMEDIAEXTENSIONS:
      return { ...state, extensions: action.extensions };
    default:
      return state;
  }
};

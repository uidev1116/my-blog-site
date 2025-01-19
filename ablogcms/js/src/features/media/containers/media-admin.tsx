import { connect } from 'react-redux';
import { Dispatch, bindActionCreators } from 'redux';
import * as actions from '../stores/actions';
import MediaAdminView from '../components/media-admin/media-admin-view';
import { MediaStateProps } from '../types';

function mapStateToProps(state: MediaStateProps) {
  return state;
}

function mapDispatchToProps(dispatch: Dispatch) {
  return {
    actions: bindActionCreators(actions, dispatch),
  };
}

export default connect(mapStateToProps, mapDispatchToProps)(MediaAdminView);

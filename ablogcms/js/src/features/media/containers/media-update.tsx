import { connect } from 'react-redux';
import { Dispatch, bindActionCreators } from 'redux';
import * as actions from '../stores/actions';
import MediaUpdateModal from '../components/media-update/media-update-modal';
import { MediaStateProps } from '../types';

function mapStateToProps(state: MediaStateProps) {
  return state;
}

function mapDispatchToProps(dispatch: Dispatch) {
  return {
    actions: bindActionCreators(actions, dispatch),
  };
}

export default connect(mapStateToProps, mapDispatchToProps)(MediaUpdateModal);

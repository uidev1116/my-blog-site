import { connect } from 'react-redux';
import { Dispatch, bindActionCreators } from 'redux';
import * as actions from '../stores/actions';
import MediaInsertModal from '../components/media-insert/media-insert-modal';
import { MediaStateProps } from '../types';

function mapStateToProps(state: MediaStateProps) {
  return state;
}

function mapDispatchToProps(dispatch: Dispatch) {
  return {
    actions: bindActionCreators(actions, dispatch),
  };
}

export default connect(mapStateToProps, mapDispatchToProps)(MediaInsertModal);

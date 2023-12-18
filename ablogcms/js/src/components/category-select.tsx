import React, { Component } from 'react';
import axios from 'axios';
import RichSelect from './rich-select';
import ACMSModal from './modal';

interface CategorySelectProp {
  creation: boolean;
  noneOption: boolean;
  targetDom: HTMLElement;
  etcTargetDoms: HTMLElement;
}

interface CategorySelectState {
  showModal: boolean;
  categoryAddParent: string;
  currentValue: number;
  addCategoryError: {
    nameRequired: boolean;
    codeRequired: boolean;
    codeDouble: boolean;
    codeReserved: boolean;
    scopeRequired: boolean;
  };
}

export default class CategorySelect extends Component<CategorySelectProp, CategorySelectState> {
  constructor(props) {
    super(props);

    let currentValue = props.targetDom.value;
    if (props.noneOption && !currentValue) {
      const s = window.location.href.split('?');
      if (s.length > 1) {
        const query = ACMS.Library.parseQuery(s[1]);
        if (query._cid) {
          currentValue = query._cid;
          props.targetDom.value = query._cid;
          if (props.etcTargetDoms) {
            [].forEach.call(props.etcTargetDoms, (dom) => {
              dom.value = query._cid;
            });
          }
        }
      }
    }
    this.state = {
      showModal: false,
      categoryAddParent: '',
      currentValue,
      addCategoryError: {
        nameRequired: true,
        codeRequired: true,
        codeDouble: true,
        codeReserved: true,
        scopeRequired: true,
      },
    };
    this.addCategoryFormRef = React.createRef();
    this.handleShowModal = this.handleShowModal.bind(this);
    this.handleCloseModal = this.handleCloseModal.bind(this);
    this.handleAddCategory = this.handleAddCategory.bind(this);
  }

  handleShowModal(e) {
    e.preventDefault();
    this.setState({
      showModal: true,
    });
  }

  handleCloseModal(e) {
    e.preventDefault();
    this.setState({
      showModal: false,
    });
  }

  handleAddCategory(e) {
    e.preventDefault();
    const { targetDom, etcTargetDoms } = this.props;
    const $form = $(this.addCategoryFormRef.current);
    const url = ACMS.Library.acmsLink(
      {
        tpl: 'ajax/edit/category-add-response.json',
        Query: {
          hash: Math.random(),
        },
      },
      true,
    );
    const data = ACMS.Library.getPostData($form);
    $.ajax({
      url,
      type: 'post',
      dataType: 'json',
      data,
      success: (data) => {
        if (data.isValid) {
          this.setState({
            showModal: false,
            currentValue: data.id,
          });
          targetDom.value = data.id;
          if (etcTargetDoms) {
            [].forEach.call(etcTargetDoms, (dom) => {
              dom.value = data.id;
            });
          }
        } else {
          this.setState({
            addCategoryError: {
              nameRequired: data.nameRequired !== '0',
              codeRequired: data.codeRequired !== '0',
              codeDouble: data.codeDouble !== '0',
              codeReserved: data.codeReserved !== '0',
              scopeRequired: data.scopeRequired !== '0',
            },
          });
        }
      },
    });
  }

  render() {
    const {
      creation, noneOption, targetDom, etcTargetDoms, narrowDown,
    } = this.props;
    const {
      showModal, categoryAddParent, currentValue, addCategoryError,
    } = this.state;

    return (
      <div>
        <div style={{ display: 'inline-block', width: '350px' }}>
          <RichSelect
            key={currentValue}
            defaultValue={currentValue}
            className="admin-admin-category-select"
            name="react_category_id"
            isMulti={false}
            isAsync
            creatable={false}
            clearable
            closeOnSelect
            placeholder={ACMS.i18n('entry_editor.category_placeholder')}
            noResultsText={ACMS.i18n('entry_editor.category_notfound')}
            isValidNewOption={({ label }) => !!label}
            loadOptions={async (input) => {
              const endpoint = ACMS.Library.acmsLink(
                {
                  bid: ACMS.Config.bid,
                  cid: ACMS.Config.cid,
                  keyword: input,
                  tpl: 'ajax/edit/category-assist.json',
                  Query: {
                    narrowDown,
                    currentCid: currentValue,
                  },
                },
                false,
              );
              const response = await axios.get(endpoint);
              if (noneOption) {
                response.data.unshift({
                  label: 'カテゴリーなし',
                  value: 0,
                });
              }
              return { options: response.data };
            }}
            onChange={(data) => {
              if (data && !isNaN(data.value)) {
                targetDom.value = data.value;
              } else {
                targetDom.value = '';
              }
              if (etcTargetDoms) {
                [].forEach.call(etcTargetDoms, (dom) => {
                  dom.value = targetDom.value;
                });
              }
            }}
          />
        </div>
        {creation && (
          <div
            style={{ display: 'inline-block', verticalAlign: 'top', marginLeft: '3px' }}
            id="entry-create-category-display"
          >
            <button
              type="button"
              className="acms-admin-btn-admin"
              style={{ padding: '8px 8px' }}
              onClick={this.handleShowModal}
            >
              追加
            </button>
          </div>
        )}

        <ACMSModal
          isOpen={showModal}
          onClose={this.handleCloseModal}
          title={<h3>カテゴリー追加</h3>}
          dialogStyle={{ maxWidth: '400px', borderRadius: '5px' }}
          footer={(
            <div>
              <button type="button" className="acms-admin-btn" onClick={this.handleAddCategory}>
                追加
              </button>
            </div>
          )}
        >
          <form className="acms-admin-form" style={{ marginTop: '20px' }} ref={this.addCategoryFormRef}>
            <input type="hidden" name="category[]" value="name" />
            <input type="hidden" name="category[]" value="code" />
            <input type="hidden" name="category[]" value="parent" />
            <input type="hidden" name="category[]" value="scope" />
            <input type="hidden" name="category[]" value="status" />
            <input type="hidden" name="category[]" value="indexing" />

            <input type="hidden" name="name:validator#required" id="validator-name-required" />
            <input type="hidden" name="code:validator#required" id="validator-code-required" />
            <input type="hidden" name="code:validator#double" id="validator-code-double" />
            <input type="hidden" name="code:validator#reserved" id="validator-code-reserved" />
            <input type="hidden" name="scope:validator#required" id="validator-scope-required" />

            <input type="hidden" name="status" value="open" />
            <input type="hidden" name="indexing" value="on" />
            <input type="hidden" name="ACMS_POST_Category_Insert" />

            <div className="acms-admin-form-group acms-admin-select2 is-single">
              {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
              <label style={{ marginBottom: '3px', fontSize: '11px', color: '#666' }}>親カテゴリー</label>
              <RichSelect
                defaultValue={null}
                className="admin-admin-category-select"
                name="react_category_id"
                isMulti={false}
                isAsync
                creatable={false}
                clearable
                closeOnSelect
                placeholder={ACMS.i18n('entry_editor.category_placeholder')}
                noResultsText={ACMS.i18n('entry_editor.category_notfound')}
                isValidNewOption={({ label }) => !!label}
                loadOptions={async (input) => {
                  const endpoint = ACMS.Library.acmsLink(
                    {
                      bid: ACMS.Config.bid,
                      cid: ACMS.Config.cid,
                      keyword: input,
                      tpl: 'ajax/edit/category-assist.json',
                      Query: {
                        narrowDown,
                      },
                    },
                    false,
                  );
                  const response = await axios.get(endpoint);
                  return { options: response.data };
                }}
                onChange={(data) => {
                  if (data && data.value) {
                    this.setState({
                      categoryAddParent: data.value,
                    });
                  } else {
                    this.setState({
                      categoryAddParent: '',
                    });
                  }
                }}
              />
              <input type="hidden" name="parent" value={categoryAddParent} />
            </div>

            <div className="acms-admin-form-group">
              {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
              <label style={{ marginBottom: '3px', fontSize: '11px', color: '#666' }}>カテゴリー名</label>
              <input type="text" name="name" className="acms-admin-form-width-full" />
              {!addCategoryError.nameRequired && (
                <div role="alert" aria-live="assertive">
                  <div data-validator-label="validator-name-required" className="validator-result-0">
                    <p className="error-text">名前が入力されていません。</p>
                  </div>
                </div>
              )}
            </div>

            <div className="acms-admin-form-group">
              {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
              <label style={{ marginBottom: '3px', fontSize: '11px', color: '#666' }}>コードネーム</label>
              <input type="text" name="code" className="acms-admin-form-width-full" />
              <div role="alert" aria-live="assertive">
                {!addCategoryError.codeRequired && (
                  <div data-validator-label="validator-code-required" className="validator-result-0">
                    <p className="error-text">コードネームが入力されていません。</p>
                  </div>
                )}
                {!addCategoryError.codeDouble && (
                  <div data-validator-label="validator-code-double" className="validator-result-0">
                    <p className="error-text">既に使用されているコードネームです。</p>
                  </div>
                )}
                {!addCategoryError.codeReserved && (
                  <div data-validator-label="validator-code-reserved" className="validator-result-0">
                    <p className="error-text">システムで予約されているキーワードです。</p>
                  </div>
                )}
              </div>
            </div>

            <div className="acms-admin-form-group">
              {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
              <label style={{ marginBottom: '3px', fontSize: '11px', color: '#666' }}>スコープ</label>
              <br />
              <input type="hidden" name="scope" value="local" />
              <div className="acms-admin-form-checkbox">
                <input type="checkbox" name="scope" value="global" id="input-checkbox-global" />
                {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
                <label htmlFor="input-checkbox-global">
                  <i className="acms-admin-ico-checkbox" />
                  下の階層のブログと共有する
                </label>
              </div>
              {!addCategoryError.scopeRequired && (
                <div role="alert" aria-live="assertive">
                  <div data-validator-label="validator-scope-tree" className="validator-result-0">
                    <p className="error-text">親カテゴリーと異なる共有設定にすることはできません。</p>
                  </div>
                </div>
              )}
            </div>
            <input type="hidden" name="formToken" value={window.csrfToken} />
          </form>
        </ACMSModal>
      </div>
    );
  }
}

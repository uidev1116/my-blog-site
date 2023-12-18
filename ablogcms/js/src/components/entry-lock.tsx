import React, { useState, useEffect } from 'react'
import { render } from 'react-dom'
import axios from 'axios'
import { throttle } from 'throttle-debounce'
import Modal from './modal'

/**
 * エントリーの排他制御
 * エントリーが編集中であることをPOSTする
 */
const dispatchEntryExclusiveControl = () => {
  if (
    ACMS.Config.admin === 'entry-edit' ||
    ACMS.Config.admin === 'entry_editor'
  ) {
    const entryEditForm = document.querySelector('#entryForm')
    if (entryEditForm) {
      // ToDo: カテゴリー選択やメディアの画像選択などのイベントもフックしたい
      entryEditForm.addEventListener(
        'input',
        throttle(10000, async () => {
          const params = new URLSearchParams()
          params.append('ACMS_POST_Entry_Lock_Exec', true)
          params.append('rvid', ACMS.Config.rvid || 0)
          params.append('eid', ACMS.Config.eid || 0)
          params.append('formToken', window.csrfToken)

          await axios({
            method: 'POST',
            url: window.location.href,
            data: params,
          })
        }),
      )
    }
  }
}

/**
 * エントリーがロック状態か確認する
 */
const checkEntryLock = async () => {
  const params = new URLSearchParams()
  params.append('ACMS_POST_Entry_Lock_Check', true)
  params.append('rvid', ACMS.Config.rvid || 0)
  params.append('eid', ACMS.Config.eid || 0)
  params.append('formToken', window.csrfToken)

  const response = await axios({
    method: 'POST',
    url: window.location.href,
    responseType: 'json',
    data: params,
  })
  const json = response.data
  if (response.status === 200) {
    return json
  }
  return {
    locked: false,
  }
}

const EntryLockModal = () => {
  const [isOpen, setIsOpen] = useState(false)
  const [isNoteOpen, setIsNoteOpen] = useState(false)
  const [lockedInfo, setLockedInfo] = useState({
    name: '',
    icon: '',
    datetime: '',
    expire: '',
    viewLink: '',
    alertOnly: false,
  })

  const fetchLockedInfo = async () => {
    const json = await checkEntryLock()
    if (json.locked) {
      setIsOpen(true)
      setLockedInfo({
        name: json.name,
        icon: json.icon,
        datetime: json.datetime,
        expire: json.expire,
        viewLink: json.viewLink,
        alertOnly: json.alertOnly,
      })
    }
  }

  const handleNoteButtonClick = (e: React.MouseEvent<HTMLButtonElement>) => {
    e.preventDefault()
    setIsNoteOpen(!isNoteOpen)
  }

  useEffect(() => {
    fetchLockedInfo()
  }, [setIsOpen])

  const modalFooter = lockedInfo.alertOnly ? (
    <div>
      <a href={lockedInfo.viewLink} className="acms-admin-btn">
        {ACMS.i18n('entry_lock.modal.viewLink')}
      </a>
      <button
        onClick={() => {
          setIsOpen(false)
        }}
        type="button"
        className="acms-admin-btn acms-admin-btn-admin-primary acms-admin-btn-admin-search"
      >
        {ACMS.i18n('entry_lock.modal.editLink')}
      </button>
    </div>
  ) : (
    <div>
      <a
        href={lockedInfo.viewLink}
        className="acms-admin-btn acms-admin-btn-admin-info acms-admin-btn-admin-search"
      >
        {ACMS.i18n('entry_lock.modal.viewLink')}
      </a>
    </div>
  )

  return (
    <Modal
      isOpen={isOpen}
      onClose={() => {
        setIsOpen(false)
      }}
      title={<h3>{ACMS.i18n('entry_lock.modal.title')}</h3>}
      dialogStyle={{ maxWidth: '600px', marginTop: '100px' }}
      style={{ backgroundColor: 'rgba(0, 0, 0, .5)' }}
      footer={modalFooter}
    >
      <div className="acms-admin-entry-lock-modal">
        <p className="acms-admin-entry-lock-modal-lead">
          {lockedInfo.alertOnly && ACMS.i18n('entry_lock.modal.lead1')}
          {!lockedInfo.alertOnly && ACMS.i18n('entry_lock.modal.lead2')}
        </p>
        <table className="acms-admin-entry-lock-modal-table acms-admin-table-admin-edit">
          <tbody>
            <tr>
              <th>{ACMS.i18n('entry_lock.modal.nameLabel')}</th>
              <td>
                <div className="acms-admin-entry-lock-modal-user">
                  <img
                    src={`${ACMS.Config.root}archives/${lockedInfo.icon}`}
                    width="24"
                    height="24"
                    className="acms-admin-user"
                    alt={lockedInfo.name}
                  />
                  <span>{lockedInfo.name}</span>
                </div>
              </td>
            </tr>
            <tr>
              <th>{ACMS.i18n('entry_lock.modal.editDateLabel')}</th>
              <td>{lockedInfo.datetime}</td>
            </tr>
            {!lockedInfo.alertOnly && (
              <tr>
                <th>{ACMS.i18n('entry_lock.modal.lockExpireLabel')}</th>
                <td>{lockedInfo.expire}</td>
              </tr>
            )}
          </tbody>
        </table>
        {lockedInfo.alertOnly && (
          <p className="acms-admin-entry-lock-modal-lead">
            {ACMS.i18n('entry_lock.modal.lead3')}
          </p>
        )}
        {lockedInfo.alertOnly && (
          <button
            type="button"
            onClick={handleNoteButtonClick}
            className="acms-admin-entry-lock-modal-note-label"
          >
            <i className="acms-admin-icon-tooltip" />
            <span>{ACMS.i18n('entry_lock.modal.noteLabel')}</span>
          </button>
        )}
        {lockedInfo.alertOnly && isNoteOpen && (
          <p className="acms-admin-entry-lock-modal-note">
            {ACMS.i18n('entry_lock.modal.note')}
          </p>
        )}
      </div>
    </Modal>
  )
}

export default (target: HTMLElement) => {
  dispatchEntryExclusiveControl()

  render(<EntryLockModal />, target)
}

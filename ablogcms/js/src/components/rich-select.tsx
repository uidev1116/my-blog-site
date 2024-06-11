import React, { Component } from 'react'
import styled from 'styled-components'
import axiosLib from '../lib/axios'
import Select, { Creatable, Async } from './react-select-styled'
import 'react-select/dist/react-select.css'

const CreatableWrap = styled.div`
  .Select.is-focused:not(.is-open) > .Select-control {
    box-shadow:
      0 0 0 2px rgba(19, 122, 243, 0.4),
      inset 0 1px 1px rgba(0, 0, 0, 0.1);
  }
`

type RichSelectProp = {
  isMulti?: boolean
  isAsync?: boolean
  name?: string
  className?: string
  closeOnSelect?: boolean
  creatable?: boolean
  clearable?: boolean
  dataUrl?: string
  defaultValue?: string
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  loadOptions?: (
    inputValue: string,
    callback: (options: any) => void,
  ) => Promise<any> | void
  placeholder?: string
  noResultsText?: string
  promptTextCreator?: (label: string) => string
  isValidNewOption?: boolean
  onChange?: (value: string) => void
}

type Tag = {
  label: string
  value: string
}

type RichSelectState = {
  show: 'block' | 'none'
  value: string
  options: Tag[]
}

export default class RichSelect extends Component<
  RichSelectProp,
  RichSelectState
> {
  static defaultProps = {
    creatable: false,
    clearable: false,
    dataUrl: '',
    defaultValue: [],
    isMulti: false,
    isAsync: false,
    closeOnSelect: true,
    name: 'defaultName',
    className: 'acms-admin-rich-select',
    placeholder: '',
    noResultsText: '',
    promptTextCreator: (label) => `Create ${label}`,
    onChange: () => {}, // eslint-disable-line @typescript-eslint/no-empty-function
    loadOptions: () => {}, // eslint-disable-line @typescript-eslint/no-empty-function
    isValidNewOption: ({ label }) => !!label,
  }

  constructor(props) {
    super(props)
    this.state = {
      show: 'none',
      value: props.defaultValue,
      options: [],
    }
    this.handleChange = this.handleChange.bind(this)
  }

  componentDidMount() {
    const { dataUrl } = this.props
    if (dataUrl) {
      axiosLib.get(dataUrl).then((res) => {
        if (res.data) {
          this.setState({
            options: res.data,
          })
        }
      })
    }
    setTimeout(() => {
      this.setState({ show: 'block' })
    }, 100)
  }

  handleChange(value) {
    const { onChange } = this.props

    this.setState({ value }, () => {
      onChange(this.state.value)
    })
  }

  render() {
    const {
      isMulti,
      isAsync,
      name,
      className,
      closeOnSelect,
      creatable,
      clearable,
      placeholder,
      noResultsText,
      promptTextCreator,
      isValidNewOption,
      filterOption,
      loadOptions,
    } = this.props
    const { options, value, show } = this.state
    // eslint-disable-next-line no-nested-ternary
    const SelectComponent = creatable ? Creatable : isAsync ? Async : Select

    return (
      <CreatableWrap style={{ display: show }}>
        <SelectComponent
          value={value}
          name={name}
          className={className}
          multi={isMulti}
          closeOnSelect={closeOnSelect}
          onChange={this.handleChange}
          options={options}
          clearable={clearable}
          placeholder={placeholder}
          noResultsText={noResultsText}
          promptTextCreator={promptTextCreator}
          isValidNewOption={isValidNewOption}
          loadOptions={loadOptions}
          {...(filterOption && { filterOption })}
        />
      </CreatableWrap>
    )
  }
}

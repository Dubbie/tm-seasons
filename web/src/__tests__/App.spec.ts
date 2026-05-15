import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import App from '../App.vue'

describe('App', () => {
  it('renders primary navigation', () => {
    const wrapper = mount(App, {
      global: {
        stubs: {
          RouterLink: {
            template: '<a><slot /></a>',
          },
          RouterView: {
            template: '<div />',
          },
        },
      },
    })

    expect(wrapper.text()).toContain('Seasons')
  })
})

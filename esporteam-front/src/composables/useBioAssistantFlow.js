import { useBioAssistantOnboarding } from './useBioAssistantOnboarding.js'
import { useBioSuggestionWizard } from './useBioSuggestionWizard.js'

export function useBioAssistantFlow({ onboarding: onboardingOptions, wizard: wizardOptions } = {}) {
  const onboarding = useBioAssistantOnboarding(onboardingOptions)
  const wizard = useBioSuggestionWizard(wizardOptions)

  async function evaluate(profile) {
    const invited = onboarding.evaluate(profile)
    if (onboarding.open.value) await wizard.show()
    return invited
  }

  async function openManual() {
    onboarding.showManual()
    await wizard.show()
  }

  function close() {
    wizard.close()
    onboarding.close()
  }

  function dismissForSession() {
    wizard.close()
    onboarding.dismissForSession()
  }

  async function generate({ hasSportContext }) {
    if (!hasSportContext) return { reason: 'missing_context' }
    const suggestion = await wizard.generate()
    return { suggestion, reason: wizard.contextMissing.value ? 'missing_context' : null }
  }

  async function accept() {
    const suggestion = await wizard.acceptSuggestion()
    if (suggestion?.bio) onboarding.complete()
    return suggestion
  }

  return {
    onboarding,
    wizard,
    evaluate,
    openManual,
    close,
    dismissForSession,
    generate,
    accept,
  }
}

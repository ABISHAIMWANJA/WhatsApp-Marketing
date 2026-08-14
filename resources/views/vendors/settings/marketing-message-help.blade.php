<x-lw.modal id="lwMMOnboardingHelpModal" :header="__tr('Marketing Message Onboarding Help')" :hasForm="false">
    <dt>{{  __tr('Step 1:') }}</dt>
    <dd>{!!  __tr('Navigate to the __appDashboard__ > __whatsApp__ > __quickstart__ panel.', [
            '__appDashboard__' => '<strong>App Dashboard</strong>',
            '__whatsApp__' => '<strong>WhatsApp</strong>',
            '__quickstart__' => '<strong>Quickstart</strong>',
    ]) !!}</dd>
    <dt>{{  __tr('Step 2:') }}</dt>
     <dd>{!!  __tr('On the __quickstart__ page, locate the “Improve ROI with Marketing Messages API for WhatsApp” card and click the “Get started” button.', [
            '__quickstart__' => '<strong>Quickstart</strong>',
    ]) !!}</dd>
    <dd>
        <img src="{{ asset('imgs/help/marketing_message_step_1.png') }}" alt="">
    </dd>
    <dt>{{  __tr('Step 3:') }}</dt>
     <dd>{!!  __tr('Click on __continue__ to accept the Terms of Service.', [
            '__continue__' => '<strong>“Continue to integration guide”</strong>',
    ]) !!}</dd>
    <dd>
        <img src="{{ asset('imgs/help/marketing_message_step_2.png') }}" alt="">
    </dd>
    <dt>{{  __tr('Step 4:') }}</dt>
    <dd>{!!  __tr('Now in __appName__ click on __reSyncBtn__ button to get updated Marketing Message onboarding status.', [
            '__appName__' => '<strong>WhatsJet</strong>',
            '__reSyncBtn__' => '<code>Re-sync Phone Numbers</code>',
    ]) !!}</dd>
    <dd>
</x-lw.modal>
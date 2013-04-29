<?php

namespace {{ namespace }}\Job;

{% block class_definition %}
class {{ job }}Job
{% endblock class_definition %}
{
{% block class_body %}
    public function perform()
    {
        // create job
    }
{% endblock class_body %}
}

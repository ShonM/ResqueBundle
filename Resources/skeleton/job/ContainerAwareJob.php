<?php

namespace {{ namespace }}\Job;

{% block use_statements %}
use ShonM\ResqueBundle\Job\ContainerAwareJob;
{% endblock use_statements %}

{% block class_definition %}
class {{ job }}Job extends ContainerAwareJob
{% endblock class_definition %}
{
{% block class_body %}
    public function perform()
    {
        // create job
    }
{% endblock class_body %}
}

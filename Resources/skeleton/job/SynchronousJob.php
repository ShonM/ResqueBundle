<?php

namespace {{ namespace }}\Job;

{% block use_statements %}
use ShonM\ResqueBundle\Job\JobInterface;
use ShonM\ResqueBundle\Job\SynchronousInterface;
{% endblock use_statements %}

{% block class_definition %}
class {{ job }}Job implements JobInterface, SynchronousInterface
{% endblock class_definition %}
{
{% block class_body %}
    public function perform()
    {
        // create job
    }
{% endblock class_body %}
}

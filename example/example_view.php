<?php
/**
 * Пример представления, в котором к модели добавляется поведение для защиты от спама.
 *
 * @copyright  Copyright (c) 2013 Kuponator.ru
 * @author     Yaroslav Usatikov <ys@kuponator.ru>
 */
?>
<div class="form" style="margin: 60px">

    <?php $form = $this->beginWidget('CActiveForm', array(
        'id' => 'email-form',
        'enableAjaxValidation' => false,
    )); ?>

    <p class="note">Fields with <span class="required">*</span> are required.</p>

    <?php echo $form->errorSummary($model); ?>

    <div class="row">
        <?php echo $form->labelEx($model, 'name'); ?>
        <?php echo $form->textField($model, 'name', array('size' => 60, 'maxlength' => 255)); ?>
        <?php echo $form->error($model, 'name'); ?>
    </div>

    <div class="row">
        <?php echo $form->labelEx($model, 'text'); ?>
        <?php echo $form->textArea($model, 'text', array('rows' => 5, 'cols' => 60)); ?>
        <?php echo $form->error($model, 'text'); ?>
    </div>

    <? if ($model->getCaptcha()) : ?>

    <div class="row">
        <img src="<?= $model->getCaptcha() ?>" alt="Защита от спама" width="200" height="60" />
        <?php echo $form->labelEx($model, 'captcha'); ?>
        <?php echo $form->textField($model, 'captcha', array('size' => 60, 'maxlength' => 255)); ?>
        <?php echo $form->error($model, 'captcha'); ?>
    </div>

    <? endif; ?>

    <div class="row buttons">
        <?php echo CHtml::submitButton('Save'); ?>
    </div>

    <?php $this->endWidget(); ?>

</div><!-- form -->

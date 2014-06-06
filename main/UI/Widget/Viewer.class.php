<?php
/**
 * Конструктор вьюверов
 * @author Alex Gorbylev <gorbylev@adonweb.ru>
 * @date 2012.03.11
 */
final class Viewer extends StaticFactory {
	/**
	 * @var PartViewer
	 */
	static private $view			= null;

	/**
	 * @param PartViewer $view
	 *
	 * @return void
	 */
	public static function set(PartViewer $view)
	{
		self::$view = $view;
		return /*void*/;
	}


	/**
	 * @return PartViewer
	 */
	public static function get()
	{
		if(
			!self::$view
		)
		{
			$viewResolver =
				MultiPrefixPhpViewResolver::create()->
					setViewClassName('SimplePhpView')->
					addPrefix( Config::me()->getBaseTemplatesPath().'widgets'.DS );
			$partViewer = new PartViewer($viewResolver, Model::create() );
			self::set($partViewer);
		}

		return self::$view;
	}

}
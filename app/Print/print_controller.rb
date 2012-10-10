require 'rho/rhocontroller'
require 'helpers/browser_helper'

class PrintController < Rho::RhoController
  include BrowserHelper

  # GET /Print
  def index
    t = Time.now
    @prints = Print.find_all(:conditions => {'inserted'=>t.strftime("%Y%m%d").to_i},:order => 'ressort',:orderdir => 'ASC' ) 
    unless @prints.empty? then
      puts "make rendering"   
      render :action => :index, :back => :exit
    else
      self.dbdelete
      self.update
    end
  end
  
  def dbdelete
    puts "make delete"
    Print.delete_all
  end
  
  def update    
    puts "make update"  
    url = 'http://service.tagesspiegel.de/dishes/mobile2/print.txt'
    Rho::AsyncHttp.get(
        :url => url,
        :callback => (url_for :action => :httpget_callback),
        :callback_param => "" )
    render :action => :wait
  end
  
  def httpget_callback
    if @params['status'] != 'ok'
      @@error_params = @params
      WebView.navigate (url_for :action => :show_error)
    else
      begin
        require 'json'
        t = Time.now
        $httpresult = @params['body']
        @@get_result = Rho::JSON.parse($httpresult)
        @@get_result.each do |e|
          to_save = {
            "title" => e['title'],
            "overline" => e['overline'],
            "id" => e['id'],
            "text" => e['text'],
            "teaser" => e['teaser'],
            "ressort" => e['ressort'],
            "rubrik" => e['rubrik'],
            "author" => e['author'],
            "pubDate" => e['pubDate'],
            "images" => e['images'],
            "url" => e['url'],
            "origin" => e['origin'], 
            "publish" => e['publish'],
            "isrc" => e['isrc'],
            "icap" => e['icap'],
            "inserted" => t.strftime("%Y%m%d").to_i          
          }
          exists = Print.find(:first, :conditions => {'id' => to_save['id'] })
          if exists.nil?
             exists = Print.create(to_save)
          else
            exists.update_attributes(to_save)
            exists.save
          end
        end
        @prints = Print.find(:all)
        if @prints.empty? then
          WebView.navigate ( url_for :action => :show_error )
        else
          WebView.navigate ( url_for :action => :index )
        end
      rescue Exception => e
        puts "Error: #{e}"
        @@get_result = "Error: #{e}"
      end                        
    end
  end

  def show_error
    render :action => :error, :back => url_for( :action => :index )
  end
  
  def exit
    Rho::RhoApplication.close
    System.exit
  end

  # GET /Print/{1}
  def show
    @print = Print.find(@params['id'])
    if @print
      render :action => :show, :back => url_for(:action => :index)
    else
      redirect :action => :index
    end
  end
end

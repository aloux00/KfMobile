<?php
namespace app\responser;

/**
 * 主题页面响应类
 * @package app\responser
 */
class Read extends Responser
{
    /**
     * 获取主题页面的页面数据
     * @param array $extraData 额外参数
     * @return array 版块页面的页面数据
     */
    public function index($extraData = [])
    {
        debug('begin');
        $doc = null;
        $initTime = 0;
        try {
            debug('initBegin');
            $doc = \phpQuery::newDocumentHTML($this->response['document']);
            debug('initEnd');
            $initTime = debug('initBegin', 'initEnd');
        } catch (\Exception $ex) {
            $this->handleError($ex);
        }
        $commonData = array_merge($this->getCommonData($doc), $extraData);
        $matches = [];
        $request = request();

        // 主题信息
        $tid = 0;
        $threadTitle = '';
        $fid = 0;
        $forumName = '';
        $parentFid = 0;
        $parentForumName = '';
        $hitNum = 0;
        $replyNum = 0;
        $publishTime = '';
        $tuiNum = 0;
        $pqThreadInfo = pq('td[colspan="2"]:contains("收藏本帖")')->eq(0);
        $tid = intval(pq('input[name="tid"]')->val());
        $fid = intval(pq('input[name="fid"]')->val());
        $threadTitle = trim_strip(pq('table:not(.thread1) > tr:first-child > td[colspan="2"]')->eq(0)->text());
        $pqForumNav = $pqThreadInfo->find('a[href^="thread.php?fid="]');
        $forumName = trim_strip($pqForumNav->eq($pqForumNav->length - 1)->text());
        if ($pqForumNav->length >= 2) {
            if (preg_match('/fid=(\d+)/', $pqForumNav->eq(0)->attr('href'), $matches)) {
                $parentFid = intval($matches[1]);
            }
            $parentForumName = trim_strip($pqForumNav->eq(0)->text());
        }
        if (preg_match('/点击：(\d+|-).+回复数：(\d+).+发表时间：(\d{4}-\d{2}-\d{2}\s*\d{2}:\d{2})/', $pqThreadInfo->text(), $matches)) {
            $hitNum = $matches[1];
            $replyNum = intval($matches[2]);
            $publishTime = $matches[3];
        }
        if (preg_match('/推!\s*(\d+)/', pq('#read_tui')->text(), $matches)) {
            $tuiNum = intval($matches[1]);
        }

        // 分页导航
        $currentPageNum = 1;
        $maxPageNum = 1;
        $pageParam = [];
        $pqPages = pq('.pages:first');
        if (preg_match('/-\s*(\d+)\s*-/', $pqPages->find('li > a[href="javascript:;"]')->text(), $matches)) {
            $currentPageNum = intval($matches[1]);
        }
        if (preg_match('/(?<!\w)page=(\d+)/', $pqPages->find('li:last-child > a')->attr('href'), $matches)) {
            $maxPageNum = intval($matches[1]);
        }
        $pageParam = http_build_query($request->except('page'));

        // 楼层
        $floorList = [];
        foreach (pq('.readtext') as $floor) {
            $floorList[] = $this->floor(pq($floor));
        }

        // 回复验证字段
        $replyVerify = pq('input[name="verify"]')->val();

        // 投票区域
        $voteTitle = '';
        $voteStatus = 'open';
        $voteTotalCount = 0;
        $voteList = [];
        $votedInfo = '';
        $voteLimitNum = 0;
        $pqVoteForm = pq('form[name="vote"]');
        if ($pqVoteForm->length > 0) {
            $pqSubmit = $pqVoteForm->find('input[type="submit"]');
            if (preg_match('/限选个数：(\d+)/', $pqSubmit->parent()->text(), $matches)) {
                $voteLimitNum = intval($matches[1]);
            }
            if (!$pqSubmit->length) {
                if ($pqVoteForm->find('b > a[href*="action=modify"]')->length > 0) $voteStatus = 'voted';
                else $voteStatus = 'close';
            }
            if ($pqVoteForm->find('input[name="voteaction"][value="modify"]')->length > 0) $voteStatus = 'modify';
            $pqVoteArea = $pqVoteForm->find('.thread1');
            $voteTitle = trim_strip($pqVoteArea->find('tr:first-child > td:first-child')->text());
            $votedInfo = trim($pqVoteArea->find('tr:last-child > td')->html());
            $pqVoteItem = $pqVoteArea->children('tr');
            foreach ($pqVoteItem->slice(1, $pqVoteArea->length - 2) as $item) {
                $pqItem = pq($item);
                $itemType = '';
                $itemValue = '';
                $itemName = '';
                $itemVoteCount = 0;

                $pqItemName = $pqItem->find('td:first-child');
                $pqInput = $pqItemName->find('input');
                if ($pqInput->length > 0) {
                    $itemType = $pqInput->attr('type');
                    $itemValue = $pqInput->val();
                }
                $itemName = trim_strip($pqItemName->find('b')->text());

                if (preg_match('/(\d+|\*)/', $pqItem->find('td:nth-child(2)')->text(), $matches)) {
                    if ($matches[1] === '*') $itemVoteCount = -1;
                    else {
                        $itemVoteCount = intval($matches[1]);
                        $voteTotalCount += $itemVoteCount;
                    }
                }

                $voteList[] = [
                    'itemType' => $itemType,
                    'itemValue' => $itemValue,
                    'itemName' => $itemName,
                    'itemVoteCount' => $itemVoteCount,
                ];
            }
        }

        $data = [
            'tid' => $tid,
            'threadTitle' => $threadTitle,
            'fid' => $fid,
            'forumName' => $forumName,
            'parentFid' => $parentFid,
            'parentForumName' => $parentForumName,
            'hitNum' => $hitNum,
            'replyNum' => $replyNum,
            'publishTime' => $publishTime,
            'tuiNum' => $tuiNum,
            'currentPageNum' => $currentPageNum,
            'prevPageNum' => $currentPageNum > 1 ? $currentPageNum - 1 : 1,
            'nextPageNum' => $currentPageNum < $maxPageNum ? $currentPageNum + 1 : $maxPageNum,
            'maxPageNum' => $maxPageNum,
            'pageParam' => $pageParam,
            'floorList' => $floorList,
            'replyVerify' => $replyVerify,
            'voteTitle' => $voteTitle,
            'voteStatus' => $voteStatus,
            'voteTotalCount' => $voteTotalCount,
            'voteList' => $voteList,
            'votedInfo' => $votedInfo,
            'voteLimitNum' => $voteLimitNum,
        ];
        debug('end');
        trace('phpQuery解析用时：' . debug('begin', 'end') . 's' . '（初始化：' . $initTime . 's）');
        if (config('app_debug')) trace('响应数据：' . json_encode($data, JSON_UNESCAPED_UNICODE));
        return array_merge($commonData, $data);
    }

    /**
     * 获取楼层的页面数据
     * @param \phpQueryObject $pqFloor
     * @return array 楼层的页面数据
     */
    protected function floor($pqFloor)
    {
        $matches = [];

        // 楼层顶部信息
        $pid = '';
        $floorNum = 0;
        $publishTime = '';
        $sign = '';
        $pqFloorTop = $pqFloor->prev('.readlou');
        $pid = $pqFloorTop->prev('a')->attr('name');
        $pqFloorTopInfo = $pqFloorTop->find('> div:nth-child(2)');
        if (preg_match('/(\d+)楼/', $pqFloorTopInfo->find('span:first-child')->text(), $matches)) {
            $floorNum = intval($matches[1]);
        }
        $infoText = $pqFloorTopInfo->find('span:last-child')->text();
        if (preg_match('/发表于：(\d{4}-\d{2}-\d{2}\s*\d{2}:\d{2})/', $infoText, $matches)) {
            $publishTime = $matches[1];
        }
        if (preg_match('/\(\s(.+)\s\)/', $infoText, $matches)) {
            $sign = trim_strip($matches[1]);
        }

        // 楼层用户信息
        $avatar = '';
        $uid = 0;
        $userName = '';
        $smLevel = '';
        $smColor = '';
        $avatarType = 1;
        if (preg_match('/#\w+/', $pqFloor->attr('style'), $matches)) {
            $smColor = $matches[0];
        }
        $pqAvatar = $pqFloor->find('.readidm');
        if ($pqAvatar->length > 0) {
            $avatarType = 2;
        } else {
            $pqAvatar = $pqFloor->find('.readidms');
        }
        if ($avatarType === 2) {
            if (preg_match('/url\((.+?)\.png\)/i', $pqAvatar->attr('style'), $matches)) {
                $avatar = $matches[1] . 's.png';
            }
            $pqUserLink = $pqAvatar->find('.readidmleft a');
            if (preg_match('/uid=(\d+)/i', $pqUserLink->attr('href'), $matches)) {
                $uid = intval($matches[1]);
            }
            $userName = trim_strip($pqUserLink->text());
            $smLevel = trim_strip($pqAvatar->find('.readidmright')->text());
        } else {
            $avatar = $pqAvatar->find('.pic')->attr('src');
            $pqUser = $pqAvatar->find('.readidmsbottom');
            $pqUserLink = $pqUser->find('a');
            if (preg_match('/uid=(\d+)/', $pqUserLink->attr('href'), $matches)) {
                $uid = intval($matches[1]);
            }
            $userName = trim_strip($pqUserLink->text());
            $pqUserContents = $pqUser->contents();
            $smLevel = str_replace('级神秘', '', trim_strip($pqUser->contents()->eq($pqUserContents->length - 1)->text()));
        }
        if (strpos($avatar, 'none.gif') > 0) $avatar = '';

        $content = $this->getFloorContent($pqFloor->find('tr:first > td'));

        return [
            'pid' => $pid,
            'floorNum' => $floorNum,
            'publishTime' => $publishTime,
            'sign' => $sign,
            'avatar' => $avatar,
            'uid' => $uid,
            'userName' => $userName,
            'smLevel' => $smLevel,
            'smColor' => $smColor,
            'content' => $content,
        ];
    }

    /**
     * 获取楼层内容
     * @param \phpQueryObject $pqFloor
     * @return string 楼层内容
     */
    protected function getFloorContent($pqFloor)
    {
        // 删除头像节点
        $pqFloor->find('.readidms, .readidm')->remove();

        //dump($pqFloor->html());
        // 替换楼层内容
        $pqFloor->html($this->replaceFloorContent($pqFloor->html()));

        // 处理fieldset节点
        foreach ($pqFloor->find('fieldset') as $node) {
            $pqNode = pq($node);
            if ($pqNode->is('.read_fds')) {
                $pqNode->attr('class', 'fieldset-alert');
            } elseif ($pqNode->find('legend:contains("Quote:")')->length > 0) {
                $pqNode->replaceWith(
                    '<blockquote class="blockquote"><p>' . str_replace('<legend>Quote:</legend>', '', $pqNode->html()) . '</p></blockquote>'
                );
            } elseif ($pqNode->find('legend:contains("此帖售价")')->length > 0) {
                $pqLegend = $pqNode->find('legend');
                $buyInfo = '';
                $price = 0;
                $matches = [];
                if (preg_match('/此帖售价\s*(\d+)\s*KFB,已有\s*(\d+)\s*人购买/i', $pqLegend->html(), $matches)) {
                    $price = intval($matches[1]);
                    $buyInfo = sprintf('售价 %d KFB，有 %d 人购买', $price, $matches[2]);
                }
                $pqLegend->contents()->get(0)->textContent = $buyInfo;
                $pqLegend->find('select')->addClass('custom-select')->find('option:first-child')->text('名单');
                $pqBuyBtn = $pqLegend->find('input[type="button"]');
                $pid = 0;
                if (preg_match('/pid=(\w+)/i', $pqBuyBtn->attr('onclick'), $matches)) {
                    $pid = $matches[1];
                }
                $pqBuyBtn->replaceWith(
                    sprintf(
                        '<button class="btn btn-warning btn-sm buy-thread-btn" data-pid="%s" data-price="%d" type="button">' .
                        '  <i class="fa fa-shopping-cart" aria-hidden="true"></i> 购买' .
                        '</button>',
                        $pid,
                        $price
                    )
                );
            } elseif ($pqNode->find('legend:contains("Copy code")')->length > 0) {
                $pqNode->find('legend')->remove();
                $codeHtml = $pqNode->html();
                $pqNode->replaceWith(
                    '<div class="code-area"><a class="copy-code" href="#" role="button">复制代码</a>' .
                    '<pre class="pre-scrollable">' . $codeHtml . '</pre></div>'
                );
            }
        }

        // 处理编辑标志
        foreach ($pqFloor->find('div[id^="alert_"]') as $node) {
            $pqNode = pq($node);
            $pqNode->replaceWith('<div class="floor_edit_mark">' . $pqNode->html() . '</div>');
        }

        // 处理附件节点
        $pqFloor->find('div[id^="att_"]')->addClass('attach')->removeAttr('style');
        // 处理表格节点
        $pqFloor->find('table')->addClass('table table-bordered table-sm')->removeAttr('style');

        return $pqFloor->html();
    }

    /**
     * 替换楼层内容
     * @param string $html 楼层内容的HTML代码
     * @return string 替换后的楼层内容
     */
    protected function replaceFloorContent($html)
    {
        $html = preg_replace('/<strike>(.+?)<\/strike>/i', '<s>$1</s>', $html);
        $html = preg_replace_callback('/<font size="(\d+)">(.+?)<\/font>/i',
            function ($matches) {
                $fontSize = 14;
                switch (intval($matches[1])) {
                    case 1:
                        $fontSize = 10;
                        break;
                    case 2:
                        $fontSize = 13;
                        break;
                    case 3:
                        $fontSize = 16;
                        break;
                    case 4:
                        $fontSize = 18;
                        break;
                    case 5:
                        $fontSize = 24;
                        break;
                    case 6:
                        $fontSize = 32;
                        break;
                    case 7:
                        $fontSize = 48;
                        break;
                }
                return sprintf('<span style="font-size: %dpx;">%s</span>', $fontSize, $matches[2]);
            },
            $html
        );
        $html = preg_replace('/<font color="([#\w]+)">(.+?)<\/font>/i', '<span style="color: $1;">$2</span>', $html);
        $html = preg_replace('/<img src="(\d+\/)/i', '<img class="smile" alt="表情" src="/$1', $html);
        $html = preg_replace('/border="0" onclick="[^"]+" onload="[^"]+"/i', 'class="img" alt="图片"', $html);
        $html = preg_replace_callback(
            '/href="([^"]+)"/i',
            function ($matches) {
                return 'href="' . convert_url(convert_to_current_domain_url($matches[1])) . '"';
            },
            $html
        );
        $html = preg_replace(
            '/\[audio\]([^\[]+)\[\/audio\](?!<\/fieldset>)/',
            '<audio src="$1" controls="controls" preload="none">[你的浏览器不支持audio标签]</audio>',
            $html
        );
        $html = preg_replace(
            '/\[video\]([^\[]+)\[\/video\](?!<\/fieldset>)/',
            '<video src="$1" controls="controls" preload="none">[你的浏览器不支持video标签]</video>',
            $html
        );
        return $html;
    }
}
